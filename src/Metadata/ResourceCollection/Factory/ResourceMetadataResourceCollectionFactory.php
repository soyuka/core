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
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

final class ResourceMetadataResourceCollectionFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;
    private $resourceMetadataFactory;
    private $defaults;
    private $converter;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->defaults = $defaults + ['attributes' => []];
        $this->converter = new CamelCaseToSnakeCaseNameConverter();
    }

    public function create(string $resourceClass): ResourceCollection
    {
        $parentResourceCollection = null;
        if ($this->decorated) {
            try {
                $parentResourceCollection = $this->decorated->create($resourceClass);
                if ($parentResourceCollection[0] ?? false) {
                    return $parentResourceCollection;
                }
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            }
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $attributes = null;

        if (null !== $resourceMetadata->getAttributes() || [] !== $this->defaults['attributes']) {
            $attributes = (array) $resourceMetadata->getAttributes();
            foreach ($this->defaults['attributes'] as $key => $value) {
                if (!isset($attributes[$key])) {
                    $attributes[$key] = $value;
                }
            }
        }

        $resource = new Resource();

        foreach ($attributes as $key => $value) {
            $camelCaseKey = $this->converter->denormalize($key);
            if ((($key === 'identifiers') || ($key === 'validation_groups')) && !is_array($key)) {
                $value = [$value];
            }
            $resource->{$camelCaseKey} = $value;
        }

        $resource->operations = [];

        foreach ($this->createOperations($resourceMetadata->getItemOperations(), OperationType::ITEM) as $operationName => $operation) {
            $operation->shortName = $resourceMetadata->getShortName();
            $resource->operations[$operationName] = $operation;
        }

        foreach ($this->createOperations($resourceMetadata->getCollectionOperations(), OperationType::COLLECTION) as $operationName => $operation) {
            $operation->shortName = $resourceMetadata->getShortName();
            $resource->operations[$operationName] = $operation;
        }

        $resource->shortName = $resourceMetadata->getShortName();
        $resource->description = $resourceMetadata->getDescription();
        $resource->class = $resourceClass;
        $resource->types = [$resourceMetadata->getIri()];
        $resource->graphQl = $resourceMetadata->getGraphql(); // Transformation à faire ici

        return new ResourceCollection([$resource]);
    }

    /**
     * Returns the resource from the decorated factory if available or throws an exception.
     *
     * @throws ResourceClassNotFoundException
     */
    private function handleNotFound(?ResourceCollection $parentResourceCollection, string $resourceClass): ResourceCollection
    {
        if (null !== $parentResourceCollection) {
            return $parentResourceCollection;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }

    private function createOperations(array $operations, string $type): iterable
    {
        foreach ($operations as $operationName => $operation) {
            $newOperation = new Operation(method: $operation['method']);

            if (!$newOperation) { // Comment s'assure-t-on qu'il existe bien ?
                dd('STRANGE EDGE CASE FAILED');
            }

            if (isset($operation['path'])) {
                $newOperation->uriTemplate = $operation['path'];
                unset($operation['path']);
            }


            foreach ($operation as $operationKey => $operationValue) {
                if ((($operationKey === 'identifiers') || ($operationKey === 'validation_groups')) && !is_array($operationKey)) {
                    $operationValue = [$operationValue];
                }
                $newOperation->{$this->converter->denormalize($operationKey)} = $operationValue;
            }

            yield $operationName.($type === OperationType::COLLECTION ? '_collection' : '') => $newOperation;
        }
    }
}
