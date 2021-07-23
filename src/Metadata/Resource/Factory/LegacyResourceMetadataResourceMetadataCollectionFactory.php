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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

final class LegacyResourceMetadataResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use DeprecationMetadataTrait;
    private $decorated;
    private $resourceMetadataFactory;
    private $defaults;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->defaults = $defaults + ['attributes' => []];
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            return $resourceMetadataCollection;
        }

        $attributes = $resourceMetadata->getAttributes() ?? [];

        foreach ($this->defaults['attributes'] as $key => $value) {
            if (!$value) {
                continue;
            }

            if (!isset($attributes[$key])) {
                $attributes[$key] = $value;
            }
        }

        $resource = (new ApiResource())
            ->withShortName($resourceMetadata->getShortName())
            ->withDescription($resourceMetadata->getDescription())
            ->withClass($resourceClass)
            ->withTypes([$resourceMetadata->getIri() ?? $resourceMetadata->getShortName()]);

        foreach ($attributes as $key => $value) {
            [$key, $value, $hasNoEquivalence] = $this->getKeyValue($key, $value);
            if ($hasNoEquivalence) {
                continue;
            }

            $resource = $resource->{'with'.ucfirst($key)}($value);
        }

        $operations = [];
        foreach ($this->createOperations($resourceMetadata->getItemOperations(), OperationType::ITEM, $resource) as $operationName => $operation) {
            $operations[$operationName] = $operation->withShortName($resourceMetadata->getShortName());
        }

        foreach ($this->createOperations($resourceMetadata->getCollectionOperations(), OperationType::COLLECTION, $resource) as $operationName => $operation) {
            if (!$operation->getUriTemplate() && !$operation->getRouteName() && $operation->getIdentifiers()) {
                $operation = $operation->withIdentifiers([]);
            }

            $operations[$operationName] = $operation->withShortName($resourceMetadata->getShortName());
        }

        if (!$resourceMetadata->getGraphql()) {
            // GraphQl can be null or an empty array, an empty array doesn't disable graphql type creation
            $resourceMetadataCollection[] = $resource->withGraphQlOperations($resourceMetadata->getGraphql())->withOperations($operations);

            return $resourceMetadataCollection;
        }

        $graphQlOperations = [];
        foreach ($resourceMetadata->getGraphql() as $operationName => $operation) {
            if (false !== strpos($operationName, 'query') || isset($operation['item_query']) || isset($operation['collection_query'])) {
                $graphQlOperation = new Query(collection: isset($operation['collection_query']) || false !== strpos($operationName, 'collection'), name: $operationName);
            } else {
                $graphQlOperation = new Mutation(name: $operationName, description: ucfirst("{$operationName}s a {$resourceMetadata->getShortName()}."));
            }

            $graphQlOperation = $graphQlOperation
                ->withArgs($operation['args'] ?? null)
                ->withResolver($operation['item_query'] ?? $operation['collection_query'] ?? $operation['mutation'] ?? null);

            foreach ($operation as $key => $value) {
                [$key, $value, $hasNoEquivalence] = $this->getKeyValue($key, $value);
                if ($hasNoEquivalence) {
                    continue;
                }

                $graphQlOperation = $graphQlOperation->{'with'.ucfirst($key)}($value);
            }

            if (null === $graphQlOperation->getCompositeIdentifier()) {
                $graphQlOperation = $graphQlOperation->withCompositeIdentifier(true);
            }

            $graphQlOperation = $graphQlOperation->withResource($resource);

            if ('update' === $operationName && $graphQlOperation instanceof Mutation && $graphQlOperation->getMercure()) {
                $graphQlOperations['update_subscription'] = (new Subscription(name: 'update_subscription', description: "Subscribes to the $operationName event of a {$graphQlOperation->getShortName()}."))->withOperation($graphQlOperation);
            }

            $graphQlOperations[$operationName] = $graphQlOperation;
        }

        $resourceMetadataCollection[] = $resource->withOperations($operations)->withGraphQlOperations($graphQlOperations);

        return $resourceMetadataCollection;
    }

    private function createOperations(array $operations, string $type, ApiResource $resource): iterable
    {
        $priority = 0;
        foreach ($operations as $operationName => $operation) {
            $newOperation = new Operation(method: $operation['method'], collection: OperationType::COLLECTION === $type, priority: $priority++);
            foreach ($operation as $key => $value) {
                [$key, $value, $hasNoEquivalence] = $this->getKeyValue($key, $value);
                if ($hasNoEquivalence) {
                    continue;
                }

                $newOperation = $newOperation->{'with'.ucfirst($key)}($value);
            }

            $newOperation = $newOperation->withResource($resource);

            // Default behavior in API Platform < 2.7
            if (null === $newOperation->getCompositeIdentifier()) {
                $newOperation = $newOperation->withCompositeIdentifier(true);
            }

            $newOperation = $newOperation->withExtraProperties($newOperation->getExtraProperties() + ['is_legacy_resource_metadata' => true]);
            // Avoiding operation name collision by adding _collection, this is rewritten by the UriTemplateResourceMetadataCollectionFactory
            yield sprintf('%s%s', $newOperation->getRouteName() ?? $operationName, OperationType::COLLECTION === $type ? '_collection' : '') => $newOperation;
        }
    }
}
