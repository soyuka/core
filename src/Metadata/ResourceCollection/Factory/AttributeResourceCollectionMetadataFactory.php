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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Attributes\Resource;
use ApiPlatform\Core\Attributes\Operation;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceMetadataCollection;

/**
 * Creates a resource metadata from {@see Resource} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AttributeResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface 
{
    private $decorated;
    private $defaults;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->defaults = $defaults + ['attributes' => []];
    }

    /**
     * {@inheritdoc}
     * TODO: guess uriTemplate
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $parentResourceMetadata = [];
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass)->getArrayCopy();
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;

        if (\PHP_VERSION_ID >= 80000 && $reflectionClass->getAttributes(Resource::class)) {
            foreach ($this->buildResourceOperations($reflectionClass->getAttributes(), $shortName) as $i => $resource) {
                foreach ($this->defaults['extraProperties'] ?? $this->defaults['attributes'] ?? [] as $key => $value) {
                    if (!isset($resource->extraProperties[$key])) {
                        $resource->extraProperties[$key] = $value;
                    }
                }

                if (isset($parentResourceMetadata[$i])) {
                    foreach (['shortName', 'description', 'rdfTypes', 'operations', 'graphql', 'extraProperties', 'uriTemplate'] as $property) {
                        $parentResourceMetadata[$i] = $this->createWith($parentResourceMetadata[$i], $property, $resource->{$property});
                    }
                    $parentResourceMetadata[$i] = $parentResourceMetadata[$i]->withIsNewResource(true);
                    continue;
                }

                $parentResourceMetadata[$i] = (new ResourceMetadata(
                    $resource->shortName ?? $shortName,
                    $resource->description ?? $this->defaults['description'] ?? null,
                    null,
                    null,
                    null,
                    [],
                    null,
                    $resource->graphql ?? $this->defaults['graphql'] ?? null,
                    $resource->rdfTypes ?? [],
                    $resource->extraProperties,
                    $resource->operations ?? $this->defaults['operations']
                ))->withIsNewResource(true)->withUriTemplate($resource->uriTemplate);
            }
        }

        if (!$parentResourceMetadata) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        return new ResourceMetadataCollection($parentResourceMetadata);
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     */
    private function createWith(ResourceMetadata $resourceMetadata, string $property, $value): ResourceMetadata
    {
        $upperProperty = ucfirst($property);
        $getter = "get$upperProperty";

        if (null !== $resourceMetadata->{$getter}()) {
            return $resourceMetadata;
        }

        if (null === $value) {
            return $resourceMetadata;
        }

        $wither = "with$upperProperty";

        return $resourceMetadata->{$wither}($value);
    }

    /**
     * Builds resource operations to support:
     *
     * Resource
     * Get
     * Post
     * Resource
     * Put
     * Get
     *
     * In the future, we will be able to use nested attributes (https://wiki.php.net/rfc/new_in_initializers)
     *
     * @return Resource[]
     */
    private function buildResourceOperations(array $attributes, string $shortName): array
    {
            $resources = [];
            $index = -1;
            foreach ($attributes as $attribute) {
                if ($attribute->getName() === Resource::class) {
                    $resources[++$index] = $attribute->newInstance();
                    continue;
                }

                if (!is_subclass_of($attribute->getName(), Operation::class)) {
                    continue;
                }
                
                $operation = $attribute->newInstance();

                // Operation inherits Resource defaults
                foreach ($resources[$index] as $property => $value) {
                    if ($operation->{$property} || !$value) {
                        continue;
                    }

                    $operation->{$property} = $value;
                }

                $resources[$index]->operations['_api'.str_replace(['/{', '{', '/', '}'], '_', str_replace('.{_format}', '', $operation->uriTemplate)).'_'.strtolower($operation->method)] = $operation;
            }

            return $resources;
    }
}
