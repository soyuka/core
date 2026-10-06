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

namespace ApiPlatform\JsonSchema\Generator;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\JsonSchema\Configuration;

final class PropertyMetadataAccessor
{
    /**
     * @var array<string, ApiProperty>
     */
    private array $properties = [];

    /**
     * @param array<string, mixed> $baseOptions
     */
    public function __construct(
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
        private readonly array $baseOptions,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function contextFromConfiguration(Configuration $configuration): array
    {
        $context = [];
        if ($configuration->groups) {
            $context['serializer_groups'] = $configuration->groups;
        }
        if (null !== $configuration->attributes) {
            $context['serializer_attributes'] = $configuration->attributes;
        }

        return $context;
    }

    /**
     * @param class-string         $class
     * @param array<string, mixed> $context
     *
     * @return list<string>
     */
    public function getNames(string $class, array $context): array
    {
        return array_values(iterator_to_array($this->propertyNameCollectionFactory->create($class, $this->baseOptions + $context), false));
    }

    /**
     * @param class-string         $class
     * @param array<string, mixed> $context
     */
    public function get(string $class, string $property, array $context): ApiProperty
    {
        return $this->properties[$class.'::'.$property.'::'.json_encode($context, \JSON_THROW_ON_ERROR)] ??= $this->propertyMetadataFactory->create($class, $property, $this->baseOptions + $context);
    }
}
