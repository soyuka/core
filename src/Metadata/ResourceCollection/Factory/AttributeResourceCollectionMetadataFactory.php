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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceMetadataCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource;

/**
 * Creates a resource metadata from {@see Resource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class AttributeResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;
    private $defaults;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->defaults = $defaults;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $parentResourceMetadata = [];
        if ($this->decorated) {
            throw new \Exception('not implemented');
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
                $resource->class = $resourceClass;
                foreach ($this->defaults as $key => $value) {
                    if (!$resource->{$key}) {
                        $resource->{$key} = $value;
                    }
                }

                $parentResourceMetadata[$i] = $resource;
            }
        }

        if (!$parentResourceMetadata) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        return new ResourceMetadataCollection($parentResourceMetadata);
    }

    /**
     * Builds resource operations to support:.
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
     * @return resource[]
     */
    private function buildResourceOperations(array $attributes, string $shortName): array
    {
        $resources = [];
        $index = -1;
        foreach ($attributes as $attribute) {
            if (Resource::class === $attribute->getName()) {
                $resource = $attribute->newInstance();
                $resource->shortName = $shortName;
                $resources[++$index] = $resource;
                continue;
            }

            // Create default operations
            if (!is_subclass_of($attribute->getName(), Operation::class)) {
                continue;
            }

            [$key, $operation] = $this->getOperationWithDefaults($resources[$index], $attribute->newInstance());
            $resources[$index]->operations[$key] = $operation;
        }

        // Loop again and set default operations if none where found
        foreach ($resources as $index => $resource) {
            if ($resource->operations) {
                continue;
            }

            foreach ([new Get(), new GetCollection(), new Post(), new Put(), new Patch()] as $operation) {
                [$key, $operation] = $this->getOperationWithDefaults($resources[$index], $operation);
                $resources[$index]->operations[$key] = $operation;
            }
        }

        return $resources;
    }

    private function getOperationWithDefaults(Resource $resource, Operation $operation): array
    {
        // Operation inherits Resource defaults
        foreach ($resource as $property => $value) {
            if ('operations' === $property) {
                continue;
            }

            if ($operation->{$property} || !$value) {
                continue;
            }

            $operation->{$property} = $value;
        }

        $key = sprintf('_api_%s_%s%s', $operation->uriTemplate ?: $operation->shortName, strtolower($operation->method), $operation instanceof GetCollection ? '_collection' : '');

        return [$key, $operation];
    }
}
