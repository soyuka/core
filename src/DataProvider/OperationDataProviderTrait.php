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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;

/**
 * @internal
 */
trait OperationDataProviderTrait
{
    /**
     * @var CollectionDataProviderInterface
     */
    private $collectionDataProvider;

    /**
     * @var ItemDataProviderInterface
     */
    private $itemDataProvider;

    /**
     * @var SubresourceDataProviderInterface|null
     */
    private $subresourceDataProvider;

    /**
     * @var IdentifierConverterInterface|null
     */
    private $identifierConverter;

    /**
     * Retrieves data for a collection operation.
     *
     * @return iterable|null
     */
    private function getCollectionData(array $attributes, array $context)
    {
        return $this->collectionDataProvider->getCollection($attributes['resource_class'], $attributes['collection_operation_name'], $context);
    }

    /**
     * Gets data for an item operation.
     *
     * @return object|null
     */
    private function getItemData($identifiers, array $attributes, array $context)
    {
        return $this->itemDataProvider->getItem($attributes['resource_class'], $identifiers, $attributes['item_operation_name'], $context);
    }

    /**
     * Gets data for a nested operation.
     *
     * @throws RuntimeException
     *
     * @return object|null
     */
    private function getSubresourceData($identifiers, array $attributes, array $context)
    {
        if (null === $this->subresourceDataProvider) {
            throw new RuntimeException('Subresources not supported');
        }

        return $this->subresourceDataProvider->getSubresource($attributes['resource_class'], $identifiers, $attributes['subresource_context'] + $context, $attributes['subresource_operation_name']);
    }

    /**
     * @param array $parameters - usually comes from $request->attributes->all()
     *
     * @throws InvalidIdentifierException
     */
    private function extractIdentifiers(array $parameters, array $attributes)
    {
        if (isset($attributes['item_operation_name'])) {
            $property = $attributes['identified_by'] ?? 'id';

            if (!isset($parameters[$property])) {
                throw new InvalidIdentifierException(sprintf('Parameter "%s" not found', $property));
            }

            $id = $parameters[$property];

            if (null !== $this->identifierConverter) {
                return $this->identifierConverter->convert((string) $id, $attributes['resource_class'], $attributes['identified_by'] ? [$attributes['identified_by']] : null);
            }

            return $id;
        }

        $identifiers = [];

        foreach ($attributes['subresource_context']['identifiers'] as $key => list($id, $resourceClass, $hasIdentifier)) {
            if (false === $hasIdentifier) {
                continue;
            }

            $identifiers[$id] = $parameters[$id];

            if (null !== $this->identifierConverter) {
                $identifiers[$id] = $this->identifierConverter->convert((string) $identifiers[$id], $resourceClass);
            }
        }

        return $identifiers;
    }
}
