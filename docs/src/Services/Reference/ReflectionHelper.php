<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PDG\Services\Reference;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;

class ReflectionHelper
{
    public function __construct(
        private readonly PhpDocHelper $phpDocHelper,
        private readonly OutputFormatter $outputFormatter
    ) {
    }

    public function handleParent(\ReflectionClass $reflectionClass, string $content): string
    {
        if ($parent = $reflectionClass->getParentClass()) {
            $content .= '### Extends: '.\PHP_EOL;
            $content .= '> '.$this->outputFormatter->addLink($parent).\PHP_EOL;
        }

        return $content;
    }

    public function handleImplementations(\ReflectionClass $reflectionClass, string $content): string
    {
        if (!empty($reflectionClass->getInterfaces())) {
            $content .= '### Implements '.\PHP_EOL;

            foreach ($reflectionClass->getInterfaces() as $interface) {
                $content .= '> '.$this->outputFormatter->addLink($interface).\PHP_EOL.'> '.\PHP_EOL;
            }
        }

        return $content;
    }

    public function handleClassConstants(\ReflectionClass $reflectionClass, string $content): string
    {
        $constants = [];
        foreach ($reflectionClass->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC) as $constant) {
            $constants[] = $constant;
        }
        if (!empty($constants)) {
            $content .= '## Constants: '.\PHP_EOL;
        }

        foreach ($constants as $constant) {
            $content .=
                '### '
                .$this->outputFormatter->addCssClasses($constant->getName(), ['token', 'keyword'])
                .' = ';
            if (!\is_array($constant->getValue())) {
                $content .= $constant->getValue().\PHP_EOL;
            } else {
                $content .= \PHP_EOL.'```php'.\PHP_EOL.print_r($constant->getValue(), true).'```'.\PHP_EOL;
            }

            $constantDoc = $this->phpDocHelper->getPhpDoc($constant);
            $constantText = array_filter($constantDoc->children, static function (PhpDocChildNode $constantDocNode): bool {
                return $constantDocNode instanceof PhpDocTextNode;
            });

            foreach ($constantText as $text) {
                $content .= $text.\PHP_EOL;
            }
        }

        return $content;
    }

    public function handleProperties(\ReflectionClass $reflectionClass, string $content): string
    {
        if (!empty($reflectionClass->getProperties())) {
            $content .= '## Properties: '.\PHP_EOL;
        }

        foreach ($reflectionClass->getProperties() as $property) {
            $modifier = $this->getModifier($property);
            $accessors = [];
            if ('private' === $modifier) {
                $accessors = $this->getAccessors($property);
            }
            $propertiesConstructorDocumentation = $this->phpDocHelper->getPropertiesConstructorDocumentation($reflectionClass);

            $type = $this->getTypeString($property, $propertiesConstructorDocumentation);
            $content .= "<a className=\"anchor\" href=\"#{$property->getName()}\" id=\"{$property->getName()}\">§</a>".\PHP_EOL;
            $content .= "### {$type} \${$property->getName()}".\PHP_EOL;
            if (!empty($accessors)) {
                $content .= 'Accessors: '.implode(',', $accessors).\PHP_EOL;
            }
            if (($propertyConstructor = $properties[$property->getName()] ?? false) && $propertyConstructor->description) {
                $content .= $propertyConstructor->description.\PHP_EOL;
            }

            $doc = $this->phpDocHelper->getPhpDoc($property);
            $content = $this->outputFormatter->printTextNodes($doc, $content);

            $content .= \PHP_EOL.'---'.\PHP_EOL;
        }

        return $content;
    }

    private function getModifier(\ReflectionMethod|\ReflectionProperty $reflection): string
    {
        return implode(' ', \Reflection::getModifierNames($reflection->getModifiers()));
    }

    private function getAccessors(\ReflectionProperty $property): array
    {
        $propertyName = ucfirst($property->getName());
        $accessors = [];

        foreach ($property->getDeclaringClass()->getMethods() as $method) {
            switch ($method->getName()) {
                case 'get'.$propertyName:
                case 'set'.$propertyName:
                case 'is'.$propertyName:
                    $accessors[] = $method->getName();
                    break;
                default:
                    continue 2;
            }
        }

        return $accessors;
    }

    private function getTypeString(\ReflectionProperty $reflectionProperty, array $constructorDocumentation): string
    {
        $type = $reflectionProperty->getType();
        if ($type) {
            if ($type instanceof \ReflectionUnionType) {
                $namedTypes = array_map(function (\ReflectionNamedType $namedType) {
                    return $this->outputFormatter->linkClasses($namedType);
                }, $type->getTypes());

                return implode('|', $namedTypes);
            }
            if ($type instanceof \ReflectionIntersectionType) {
                $namedTypes = array_map(function (\ReflectionNamedType $namedType) {
                    return $this->outputFormatter->linkClasses($namedType);
                }, $type->getTypes());

                return implode('&', $namedTypes);
            }
            if ($type instanceof \ReflectionNamedType) {
                return $this->outputFormatter->linkClasses($type);
            }

            return sprintf('`%s`', $type);
        }

        // Read the php doc
        $propertyTypes = $this->phpDocHelper->getPhpDoc($reflectionProperty);
        if ($varTagValues = $propertyTypes->getVarTagValues()) {
            $type = $varTagValues[0]->type;

            return $this->outputFormatter->formatType((string) $type);
        }

        if (isset($constructorDocumentation[$reflectionProperty->getName()])) {
            return $this->outputFormatter->formatType((string) $constructorDocumentation[$reflectionProperty->getName()]->type);
        }

        return '';
    }

    public function handleMethods(\ReflectionClass $reflectionClass, string $content): string
    {
        $methods = [];
        foreach ($reflectionClass->getMethods() as $method) {
            if (!$this->hasToBeSkipped($method, $reflectionClass)) {
                $methods[] = $method;
            }
        }

        if (!empty($methods)) {
            $content .= '## Methods: '.\PHP_EOL;
        }

        foreach ($methods as $method) {
            $typedParameters = $this->getParametersWithType($method);

            $content .= "<a className=\"anchor\" href=\"#{$method->getName()}\" id=\"{$method->getName()}\">§</a>".\PHP_EOL;

            $content .= '### '
                .$this->getModifier($method)
                .' '
                .$this->outputFormatter->addCssClasses($method->getName(), ['token', 'function'])
                .'( '
                .implode(', ', $typedParameters)
                .' ): '
                .$this->outputFormatter->addCssClasses($this->getReturnType($method), ['token', 'keyword'])
                .\PHP_EOL;

            $phpDoc = $this->phpDocHelper->getPhpDoc($method);
            $text = array_filter($phpDoc->children, static function (PhpDocChildNode $child): bool {
                return $child instanceof PhpDocTextNode;
            });
            $content = $this->outputFormatter->printThrowTags($phpDoc, $content);

            /** @var PhpDocTextNode $t */
            foreach ($text as $t) {
                if ($this->phpDocHelper->containsInheritDoc($t)) {
                    // Imo Trait method should not have @inheritdoc as they might not "inherit" depending
                    // on the using class
                    if ($reflectionClass->isTrait()) {
                        continue;
                    }
                    $t = $this->phpDocHelper->getInheritedDoc($method);
                }
                if (!empty((string) $t)) {
                    $content .= $t.\PHP_EOL;
                }
            }

            $content .= \PHP_EOL.'---'.\PHP_EOL;
        }

        return $content;
    }

    private function isConstruct(\ReflectionMethod $method): bool
    {
        return '__construct' === $method->getName();
    }

    private function isAccessor(\ReflectionMethod $method): bool
    {
        foreach ($method->getDeclaringClass()->getProperties() as $property) {
            if (str_contains($method->getName(), ucfirst($property->getName()))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a method is actually from a Trait or an extended class.
     */
    private function isFromExternalClass(\ReflectionMethod $method, \ReflectionClass $class): bool
    {
        return $method->getFileName() !== $class->getFileName();
    }

    private function hasToBeSkipped(\ReflectionMethod $method, \ReflectionClass $reflectionClass): bool
    {
        return $this->isFromExternalClass($method, $reflectionClass)
            || str_contains($this->getModifier($method), 'private')
            || $this->isAccessor($method)
            || $this->isConstruct($method);
    }

    /**
     * @return array<string, string>
     */
    private function getParametersWithType(\ReflectionMethod $method): array
    {
        $typedParameters = [];
        foreach ($method->getParameters() as $parameter) {
            $parameterName = $this->getParameterName($parameter);
            $type = $parameter->getType();
            if (!$type) {
                $typedParameters[] = $this->outputFormatter->addCssClasses($parameterName, ['token', 'variable']).$this->getDefaultValueString($parameter);
                continue;
            }
            if ($type instanceof \ReflectionUnionType) {
                $namedTypes = array_map(function (\ReflectionNamedType $namedType) {
                    return $this->outputFormatter->linkClasses($namedType);
                }, $type->getTypes());

                $typedParameters[] = implode('|', $namedTypes).' '.$this->outputFormatter->addCssClasses($parameterName, ['token', 'variable']).$this->getDefaultValueString($parameter);
            }
            if ($type instanceof \ReflectionIntersectionType) {
                $namedTypes = array_map(function (\ReflectionNamedType $namedType) {
                    return $this->outputFormatter->linkClasses($namedType);
                }, $type->getTypes());

                $typedParameters[] = implode('&', $namedTypes).' '.$this->outputFormatter->addCssClasses($parameterName, ['token', 'variable']).$this->getDefaultValueString($parameter);
            }
            if ($type instanceof \ReflectionNamedType) {
                $typedParameters[] = $this->outputFormatter->linkClasses($type).' '.$this->outputFormatter->addCssClasses($parameterName, ['token', 'variable']).$this->getDefaultValueString($parameter);
            }
        }

        return $typedParameters;
    }

    private function getDefaultValueString(\ReflectionParameter $parameter): string
    {
        if (!$parameter->isDefaultValueAvailable()) {
            return '';
        }

        return match ($parameter->getDefaultValue()) {
            null => ' = null',
            [] => ' = []',
            default => ' = '.$parameter->getDefaultValue()
        };
    }

    private function getParameterName(\ReflectionParameter $parameter): string
    {
        return $parameter->isPassedByReference() ? '&$'.$parameter->getName() : '$'.$parameter->getName();
    }

    private function getReturnType(\ReflectionMethod $method): string
    {
        $type = $method->getReturnType();

        if (!$type) {
            return '';
        }

        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(function (\ReflectionNamedType $reflectionNamedType): string {
                return $this->outputFormatter->linkClasses($reflectionNamedType);
            }, $type->getTypes()
            ));
        }
        if ($type instanceof \ReflectionIntersectionType) {
            return implode('&', array_map(function (\ReflectionNamedType $reflectionNamedType): string {
                return $this->outputFormatter->linkClasses($reflectionNamedType);
            }, $type->getTypes()
            ));
        }

        return $this->outputFormatter->linkClasses($type);
    }

    public function containsOnlyPrivateMethods(\ReflectionClass $reflectionClass): bool
    {
        // Do not skip empty interfaces
        if (interface_exists($reflectionClass->getName()) || trait_exists($reflectionClass->getName())) {
            return false;
        }

        if (!empty($reflectionClass->getProperties())) {
            return false;
        }

        foreach ($reflectionClass->getMethods() as $method) {
            if (!\in_array('private', \Reflection::getModifierNames($method->getModifiers()), true)) {
                return false;
            }
        }

        return true;
    }
}
