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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AttributeResources;

class AttributeResourceDataProvider implements DenormalizedIdentifiersAwareItemDataProviderInterface, RestrictedDataProviderInterface, ContextAwareCollectionDataProviderInterface
{
    public function getItem(string $resourceClass, $identifiers, string $operationName = null, array $context = [])
    {
        return new AttributeResource($identifiers['id'], 'Foo');
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        return (new AttributeResources([new AttributeResource(1, 'Foo'), new AttributeResource(2, 'Bar')]))->getArrayCopy();
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return AttributeResource::class === $resourceClass || AttributeResources::class === $resourceClass;
    }
}
