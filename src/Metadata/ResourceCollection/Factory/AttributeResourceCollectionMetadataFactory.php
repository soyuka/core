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

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Attributes\Resource;
use ApiPlatform\Core\Attributes\Operation;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;

/**
 * Creates a resource metadata from {@see ApiResource} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AttributeResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface 
{
    private $pathSegmentNameGenerator;
    private $decorated;
    private $defaults;

    public function __construct(PathSegmentNameGeneratorInterface $pathSegmentNameGenerator, ResourceCollectionMetadataFactoryInterface $decorated = null, array $defaults = [])
    {
        $this->pathSegmentNameGenerator = $pathSegmentNameGenerator;
        $this->decorated = $decorated;
        $this->defaults = $defaults + ['attributes' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): array
    {
        $parentResourceMetadata = [];
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        $shortName = (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;

        if (\PHP_VERSION_ID >= 80000 && $attributes = array_merge($reflectionClass->getAttributes(Resource::class), $reflectionClass->getAttributes(Operation::class, \ReflectionAttribute::IS_INSTANCEOF))) {

            foreach ($this->buildResourceOperations($attributes, $shortName) as $i => $resource) {
                foreach ($this->defaults['extraProperties'] ?? $this->defaults['attributes'] ?? [] as $key => $value) {
                    if (!isset($resource->extraProperties[$key])) {
                        $resource->extraProperties[$key] = $value;
                    }
                }

                if (isset($parentResourceMetadata[$i])) {
                    foreach (['shortName', 'description', 'rdfTypes', 'operations', 'graphql', 'extraProperties'] as $property) {
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
                ))->withIsNewResource(true);
            }
        }

        if (!$parentResourceMetadata) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        return $parentResourceMetadata;
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
            $resource = null;
            foreach ($attributes as $attribute) {
                if ($attribute->getName() === Resource::class) {
                    if ($resource) {
                        $resouces[] = $resource;
                    }

                    $resource = $attribute->newInstance();
                    continue;
                }
                
                $operation = $attribute->newInstance();

                // Operation inherits Resource defaults
                foreach ($resource as $property => $value) {
                    if ($operation->{$property} || !$value) {
                        continue;
                    }

                    $operation->{$property} = $value;
                }

                $resource->operations['_api_'.$this->pathSegmentNameGenerator->getSegmentName($shortName).'_'.strtolower($operation->method)] = $operation;
            }

            $resources[] = $resource;

            return $resources;
    }
}
