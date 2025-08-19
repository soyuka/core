<?php

namespace ApiPlatform\Symfony\PhpParser;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 */
class ApiFilterToParameterVisitor extends NodeVisitorAbstract
{
    private array $classFilters = [];
    private array $propertyFilters = [];
    private array $classProperties = [];

    public function enterNode(Node $node)
    {
        if (!$node instanceof Class_) {
            return null;
        }

        // 1. Find all properties of the class with their types.
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Property) {
                $propertyName = $stmt->props[0]->name->name;

                if ($stmt->type instanceof Name) {
                    $propertyType = $stmt->type->toString();
                } elseif ($stmt->type instanceof Node\Identifier) {
                    $propertyType = $stmt->type->name;
                } else {
                    $propertyType = null;
                }
                $this->classProperties[$propertyName] = $propertyType;

                // Find ApiFilter attributes on properties
                foreach ($stmt->attrGroups as $attrGroup) {
                    foreach ($attrGroup->attrs as $attr) {
                        if ($attr->name->toString() === 'ApiPlatform\Metadata\ApiFilter') {
                            $this->propertyFilters[] = [
                                'property' => $propertyName,
                                'attribute' => $attr,
                            ];
                        }
                    }
                }
            }
        }

        // 2. Find all class-level ApiFilter attributes.
        foreach ($node->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->toString() === 'ApiPlatform\Metadata\ApiFilter') {
                    $filterClass = $attr->args[0]->value->class->toString();
                    $this->classFilters[] = [
                        'class' => $filterClass,
                        'attribute' => $attr,
                    ];
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node)
    {
        if (!$node instanceof Class_ || (empty($this->classFilters) && empty($this->propertyFilters))) {
            return null;
        }

        $newParameters = [];
        $attributesToRemove = [];

        // 3. Process class-level filters.
        foreach ($this->classFilters as $filterInfo) {
            $filterClass = $filterInfo['class'];
            $attributesToRemove[] = $filterInfo['attribute'];

            switch ($filterClass) {
                case 'ApiPlatform\Doctrine\Orm\Filter\BooleanFilter':
                    foreach ($this->classProperties as $propName => $propType) {
                        if ($propType === 'bool') {
                            $newParameters[] = $this->createQueryParameter($propName);
                        }
                    }
                    break;
                case 'ApiPlatform\Doctrine\Orm\Filter\DateFilter':
                    foreach ($this->classProperties as $propName => $propType) {
                        if ($propType === '\DateTime') {
                            $newParameters[] = $this->createQueryParameter($propName . '[before]');
                            $newParameters[] = $this->createQueryParameter($propName . '[strictly_before]');
                            $newParameters[] = $this->createQueryParameter($propName . '[after]');
                            $newParameters[] = $this->createQueryParameter($propName . '[strictly_after]');
                        }
                    }
                    break;
            }
        }

        // 4. Process property-level filters.
        foreach ($this->propertyFilters as $filterInfo) {
            $propertyName = $filterInfo['property'];
            $attribute = $filterInfo['attribute'];
            $attributesToRemove[] = $attribute;

            $filterClass = $attribute->args[0]->value->class->toString();

            switch ($filterClass) {
                case 'ApiPlatform\Doctrine\Orm\Filter\SearchFilter':
                    $propertiesArg = $this->findArgument($attribute->args, 'properties');
                    $strategyArg = $this->findArgument($attribute->args, 'strategy');

                    if ($propertiesArg) {
                        foreach ($propertiesArg->value->items as $item) {
                            $newParameters[] = $this->createQueryParameter($item->key->value);
                        }
                    } elseif ($strategyArg) {
                        $newParameters[] = $this->createQueryParameter($propertyName);
                    }
                    break;

                case 'ApiPlatform\Doctrine\Orm\Filter\ExistsFilter':
                    $newParameters[] = $this->createQueryParameter('exists[' . $propertyName . ']');
                    break;

                case 'ApiPlatform\Doctrine\Orm\Filter\NumericFilter':
                    $newParameters[] = $this->createQueryParameter($propertyName);
                    break;

                case 'ApiPlatform\Doctrine\Orm\Filter\OrderFilter':
                    $newParameters[] = $this->createQueryParameter('order[' . $propertyName . ']');
                    break;

                case 'ApiPlatform\Doctrine\Orm\Filter\RangeFilter':
                    $newParameters[] = $this->createQueryParameter($propertyName . '[lt]');
                    $newParameters[] = $this->createQueryParameter($propertyName . '[gt]');
                    $newParameters[] = $this->createQueryParameter($propertyName . '[lte]');
                    $newParameters[] = $this->createQueryParameter($propertyName . '[gte]');
                    break;
            }
        }


        // 5. Remove old ApiFilter attributes from the class.
        foreach ($node->attrGroups as &$attrGroup) {
            $attrGroup->attrs = array_filter($attrGroup->attrs, function ($attr) use ($attributesToRemove) {
                return !in_array($attr, $attributesToRemove, true);
            });
        }
        $node->attrGroups = array_filter($node->attrGroups, function ($attrGroup) {
            return !empty($attrGroup->attrs);
        });

        // 6. Remove old ApiFilter attributes from properties.
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof Property) {
                foreach ($stmt->attrGroups as &$attrGroup) {
                    $attrGroup->attrs = array_filter($attrGroup->attrs, function ($attr) use ($attributesToRemove) {
                        return !in_array($attr, $attributesToRemove, true);
                    });
                }
                $stmt->attrGroups = array_filter($stmt->attrGroups, function ($attrGroup) {
                    return !empty($attrGroup->attrs);
                });
            }
        }


        // 7. Add new QueryParameter attributes to the class.
        foreach ($newParameters as $parameter) {
            $node->attrGroups[] = new AttributeGroup([$parameter]);
        }

        // Reset for next class
        $this->classFilters = [];
        $this->propertyFilters = [];
        $this->classProperties = [];

        return $node;
    }

    private function createQueryParameter(string $key): Attribute
    {
        return new Attribute(
            new Name('ApiPlatform\Metadata\QueryParameter'),
            [new Node\Arg(new String_($key), false, false, [], new Node\Identifier('key'))]
        );
    }

    private function findArgument(array $args, string $name): ?Node\Arg
    {
        foreach ($args as $arg) {
            if (isset($arg->name) && $arg->name && $arg->name->name === $name) {
                return $arg;
            }
        }
        return null;
    }
}
