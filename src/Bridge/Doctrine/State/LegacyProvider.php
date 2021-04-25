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

namespace ApiPlatform\Bridge\Doctrine\State;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\State\ProviderInterface;

/**
 * Tries each configured data provider and returns the result of the first able to handle the resource class.
 * @internal
 */
final class LegacyProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface[] $providers
     */
    public function __construct(CollectionDataProviderInterface $collectionDataProvider, ItemDataProviderInterface $itemDataProvider, SubresourceDataProviderInterface $subresourceDataProvider)
    {
    }

    public function provide(string $resourceClass, array $identifiers = [], array $context = [])
    {
        if ($this->identifiersFromSameClass($identifiers)) {
            return $this->itemDataProvider->getItem($resourceClass, $identifiers, $context);
        }

        // todo fix identifiers
        if (count($identifiers)) {
            return $this->subresourceDataProvider->getSubresource($resourceClass, $identifiers, $context);
        }

        return $this->collectionDataProvider->getCollection($resourceClass, $identifiers, $context);
    }

    public function supports(string $resourceClass, array $identifiers = [], array $context = []): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($resourceClass, $identifiers, $context)) {
                return true;
            }
        }

        return false;
    }

    private function identifiersFromSameClass($identifiers)
    {
        $class = null;
        foreach($identifiers as [$identifierClass]) {
            if ($class && $identifierClass !== $class) {
                return false;
            }

            $class = $identifierClass;
        }

        return true;
    }
}
