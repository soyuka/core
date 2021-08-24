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

namespace ApiPlatform\GraphQl\Type;

use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Builds the GraphQL types.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class TypeBuilder implements TypeBuilderInterface
{
    private $typesContainer;
    private $defaultFieldResolver;
    private $fieldsBuilderLocator;
    private $pagination;

    public function __construct(TypesContainerInterface $typesContainer, callable $defaultFieldResolver, ContainerInterface $fieldsBuilderLocator, Pagination $pagination)
    {
        $this->typesContainer = $typesContainer;
        $this->defaultFieldResolver = $defaultFieldResolver;
        $this->fieldsBuilderLocator = $fieldsBuilderLocator;
        $this->pagination = $pagination;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceObjectType(?string $resourceClass, ResourceMetadataCollection $resourceMetadataCollection, string $operationName, bool $input, bool $wrapped = false, int $depth = 0): GraphQLType
    {
        try {
            $operation = $resourceMetadataCollection->getGraphQlOperation($operationName);
        } catch (OperationNotFoundException $e) {
            $operation = (new Query())
                ->withResource($resourceMetadataCollection[0])
                ->withName($operationName)
                ->withCollection('collection_query' === $operationName);
        }

        $shortName = $operation->getShortName();

        if ($operation instanceof Mutation) {
            $shortName = $operationName.ucfirst($shortName);
        }

        if ($operation instanceof Subscription) {
            $shortName = $operationName.ucfirst($shortName).'Subscription';
        }

        if ($input) {
            $shortName .= 'Input';
        } elseif ($operation instanceof Mutation || $operation instanceof Subscription) {
            if ($depth > 0) {
                $shortName .= 'Nested';
            }
            $shortName .= 'Payload';
        }

        if ('item_query' === $operationName || 'collection_query' === $operationName) {
            // Test if the collection/item operation exists and it has different groups
            try {
                if ($resourceMetadataCollection->getGraphQlOperation($operation->isCollection() ? 'item_query' : 'collection_query')->getNormalizationContext() !== $operation->getNormalizationContext()) {
                    $shortName .= $operation->isCollection() ? 'Collection' : 'Item';
                }
            } catch (OperationNotFoundException $e) {
            }
        }

        if ($wrapped && ($operation instanceof Mutation || $operation instanceof Subscription)) {
            $shortName .= 'Data';
        }

        if ($this->typesContainer->has($shortName)) {
            $resourceObjectType = $this->typesContainer->get($shortName);
            if (!($resourceObjectType instanceof ObjectType || $resourceObjectType instanceof NonNull)) {
                throw new \LogicException(sprintf('Expected GraphQL type "%s" to be %s.', $shortName, implode('|', [ObjectType::class, NonNull::class])));
            }

            return $resourceObjectType;
        }

        $ioMetadata = $input ? $operation->getInput() : $operation->getOutput();
        if (null !== $ioMetadata && \array_key_exists('class', $ioMetadata) && null !== $ioMetadata['class']) {
            $resourceClass = $ioMetadata['class'];
        }

        $wrapData = !$wrapped && ($operation instanceof Mutation || $operation instanceof Subscription) && !$input && $depth < 1;

        $configuration = [
            'name' => $shortName,
            'description' => $operation->getDescription(),
            'resolveField' => $this->defaultFieldResolver,
            'fields' => function () use ($resourceClass, $operation, $operationName, $resourceMetadataCollection, $input, $wrapData, $depth, $ioMetadata) {
                if ($wrapData) {
                    try {
                        $queryNormalizationContext = $operation instanceof Query ? $resourceMetadataCollection->getGraphQlOperation($operationName)->getNormalizationContext() : [];
                    } catch (OperationNotFoundException $e) {
                        $queryNormalizationContext = [];
                    }

                    try {
                        $mutationNormalizationContext = $operation instanceof Mutation || $operation instanceof Subscription ? $resourceMetadataCollection->getGraphQlOperation($operationName)->getNormalizationContext() : [];
                    } catch (OperationNotFoundException $e) {
                        $mutationNormalizationContext = [];
                    }
                    // Use a new type for the wrapped object only if there is a specific normalization context for the mutation or the subscription.
                    // If not, use the query type in order to ensure the client cache could be used.
                    $useWrappedType = $queryNormalizationContext !== $mutationNormalizationContext;

                    $wrappedOperationName = $operationName;

                    if (!$useWrappedType) {
                        $wrappedOperationName = $operation instanceof Query ? $operationName : 'item_query';
                    }

                    $fields = [
                        lcfirst($operation->getShortName()) => $this->getResourceObjectType($resourceClass, $resourceMetadataCollection, $wrappedOperationName, $input, true, $depth),
                    ];

                    if ($operation instanceof Subscription) {
                        $fields['clientSubscriptionId'] = GraphQLType::string();
                        if ($operation->getMercure()) {
                            $fields['mercureUrl'] = GraphQLType::string();
                        }

                        return $fields;
                    }

                    return $fields + ['clientMutationId' => GraphQLType::string()];
                }

                $fieldsBuilder = $this->fieldsBuilderLocator->get('api_platform.graphql.fields_builder');
                $fields = $fieldsBuilder->getResourceObjectTypeFields($resourceClass, $operation, $input, $operationName, $depth, $ioMetadata);

                if ($input && $operation instanceof Mutation && null !== $mutationArgs = $operation->getArgs()) {
                    return $fieldsBuilder->resolveResourceArgs($mutationArgs, $operationName, $operation->getShortName()) + ['clientMutationId' => $fields['clientMutationId']];
                }

                return $fields;
            },
            'interfaces' => $wrapData ? [] : [$this->getNodeInterface()],
        ];

        $resourceObjectType = $input ? GraphQLType::nonNull(new InputObjectType($configuration)) : new ObjectType($configuration);
        $this->typesContainer->set($shortName, $resourceObjectType);

        return $resourceObjectType;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeInterface(): InterfaceType
    {
        if ($this->typesContainer->has('Node')) {
            $nodeInterface = $this->typesContainer->get('Node');
            if (!$nodeInterface instanceof InterfaceType) {
                throw new \LogicException(sprintf('Expected GraphQL type "Node" to be %s.', InterfaceType::class));
            }

            return $nodeInterface;
        }

        $nodeInterface = new InterfaceType([
            'name' => 'Node',
            'description' => 'A node, according to the Relay specification.',
            'fields' => [
                'id' => [
                    'type' => GraphQLType::nonNull(GraphQLType::id()),
                    'description' => 'The id of this node.',
                ],
            ],
            'resolveType' => function ($value) {
                if (!isset($value[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY])) {
                    return null;
                }

                $shortName = (new \ReflectionClass($value[ItemNormalizer::ITEM_RESOURCE_CLASS_KEY]))->getShortName();

                return $this->typesContainer->has($shortName) ? $this->typesContainer->get($shortName) : null;
            },
        ]);

        $this->typesContainer->set('Node', $nodeInterface);

        return $nodeInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourcePaginatedCollectionType(GraphQLType $resourceType, string $resourceClass, string $operationName): GraphQLType
    {
        $shortName = $resourceType->name;

        if ($this->typesContainer->has("{$shortName}Connection")) {
            return $this->typesContainer->get("{$shortName}Connection");
        }

        $paginationType = $this->pagination->getGraphQlPaginationType($resourceClass, $operationName);

        $fields = 'cursor' === $paginationType ?
            $this->getCursorBasedPaginationFields($resourceType) :
            $this->getPageBasedPaginationFields($resourceType);

        $configuration = [
            'name' => "{$shortName}Connection",
            'description' => "Connection for $shortName.",
            'fields' => $fields,
        ];

        $resourcePaginatedCollectionType = new ObjectType($configuration);
        $this->typesContainer->set("{$shortName}Connection", $resourcePaginatedCollectionType);

        return $resourcePaginatedCollectionType;
    }

    /**
     * {@inheritdoc}
     */
    public function isCollection(Type $type): bool
    {
        return $type->isCollection() && ($collectionValueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType()) && null !== $collectionValueType->getClassName();
    }

    private function getCursorBasedPaginationFields(GraphQLType $resourceType): array
    {
        $shortName = $resourceType->name;

        $edgeObjectTypeConfiguration = [
            'name' => "{$shortName}Edge",
            'description' => "Edge of $shortName.",
            'fields' => [
                'node' => $resourceType,
                'cursor' => GraphQLType::nonNull(GraphQLType::string()),
            ],
        ];
        $edgeObjectType = new ObjectType($edgeObjectTypeConfiguration);
        $this->typesContainer->set("{$shortName}Edge", $edgeObjectType);

        $pageInfoObjectTypeConfiguration = [
            'name' => "{$shortName}PageInfo",
            'description' => 'Information about the current page.',
            'fields' => [
                'endCursor' => GraphQLType::string(),
                'startCursor' => GraphQLType::string(),
                'hasNextPage' => GraphQLType::nonNull(GraphQLType::boolean()),
                'hasPreviousPage' => GraphQLType::nonNull(GraphQLType::boolean()),
            ],
        ];
        $pageInfoObjectType = new ObjectType($pageInfoObjectTypeConfiguration);
        $this->typesContainer->set("{$shortName}PageInfo", $pageInfoObjectType);

        return [
            'edges' => GraphQLType::listOf($edgeObjectType),
            'pageInfo' => GraphQLType::nonNull($pageInfoObjectType),
            'totalCount' => GraphQLType::nonNull(GraphQLType::int()),
        ];
    }

    private function getPageBasedPaginationFields(GraphQLType $resourceType): array
    {
        $shortName = $resourceType->name;

        $paginationInfoObjectTypeConfiguration = [
            'name' => "{$shortName}PaginationInfo",
            'description' => 'Information about the pagination.',
            'fields' => [
                'itemsPerPage' => GraphQLType::nonNull(GraphQLType::int()),
                'lastPage' => GraphQLType::nonNull(GraphQLType::int()),
                'totalCount' => GraphQLType::nonNull(GraphQLType::int()),
            ],
        ];
        $paginationInfoObjectType = new ObjectType($paginationInfoObjectTypeConfiguration);
        $this->typesContainer->set("{$shortName}PaginationInfo", $paginationInfoObjectType);

        return [
            'collection' => GraphQLType::listOf($resourceType),
            'paginationInfo' => GraphQLType::nonNull($paginationInfoObjectType),
        ];
    }
}