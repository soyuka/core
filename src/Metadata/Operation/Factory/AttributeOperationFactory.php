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

namespace ApiPlatform\Core\Metadata\Operation\Factory;

use ApiPlatform\Core\Attributes;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Doctrine\Common\Annotations\Reader;

final class AttributeOperationFactory implements OperationFactoryInterface
{
    private $decorated;
    private $defaults;

    public function __construct(ResourceMetadataFactoryInterface $decorated = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->defaults = $defaults + ['attributes' => []];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): array
    {
        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        $default = $this->getDefaultAttribute();
        // Maybe remove this and use the class? or directly compute path + identifiers here ? :D 
        $default->shortName = $this->getShortname($resourceClass);
        $operations = [];
        if ($attributes = $reflectionClass->getAttributes(Resource::class, \ReflectionAttribute::IS_INSTANCEOF)) {
            foreach ($attributes as $attribute) {
                if (Resource::class === $attribute->getName() && null === $attribute->newInstance()->method) {
                    continue;
                }

                $operations[] = $this->mergeDefaults($attribute->newInstance(), $default);
            }
        }

        return $this->handleNotFound($parentResourceMetadata, $resourceClass);
    }

    private function getShortname(string $resourceClass)
    {
        if (false !== $pos = strrpos($resourceClass, '\\')) {
            return substr($resourceClass, $pos + 1);
        }

        return $resourceClass;
    }

    private function mergeDefaults(Resource $resource, Resource $defaults): Resource   
    {
        foreach ($defaults as $key => $value) {
            if ($value && !$resource->{$key}) {
                $resource->{$key} = $value;
            }
        }

        return $resource;
    }

    private function getDefaultAttribute(): Resource
    {
        $default = new Resource();
        foreach ($attributes as $attribute) {
            if (Resource::class === $attribute->getName()) {
                $resource = $attribute->newInstance();
                if (null === $resource->method) {
                    $default = $resource;
                    break;
                }
            }
        }

        foreach ($this->defaults['attributes'] as $key => $value) {
            if (!isset($resource->extraProperties[$key])) {
                $resource->extraProperties[$key] = $value;
            }
        }

        foreach (['description', 'iri', 'graphql'] as $key) {
            $default->{$key} = $default->{$key} ?? $this->defaults[$key] ?? null;
        }

        return $default;
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @throws ResourceClassNotFoundException
     */
    private function handleNotFound(?ResourceMetadata $parentPropertyMetadata, string $resourceClass): ResourceMetadata
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }
}
