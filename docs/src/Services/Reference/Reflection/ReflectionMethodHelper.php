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

namespace PDG\Services\Reference\Reflection;

use PDG\Services\Reference\OutputFormatter;

class ReflectionMethodHelper
{
    use ReflectionHelperTrait;

    public function __construct(
        private readonly OutputFormatter $outputFormatter
    ) {
    }

    public function methodHasToBeSkipped(\ReflectionMethod $method, \ReflectionClass $reflectionClass): bool
    {
        return $this->isFromExternalClass($method, $reflectionClass)
            || str_contains($this->getModifier($method), 'private')
            || $this->isAccessor($method)
            || $this->isConstruct($method);
    }

    /**
     * Checks if a method is actually from a Trait or an extended class.
     */
    private function isFromExternalClass(\ReflectionMethod $method, \ReflectionClass $class): bool
    {
        return $method->getFileName() !== $class->getFileName();
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
     * @return array<string, string>
     */
    public function getParametersWithType(\ReflectionMethod $method): array
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

    public function getReturnType(\ReflectionMethod $method): string
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
}
