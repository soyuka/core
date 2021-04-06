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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\ResourceCollection\Factory\ResourceCollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * BC layer with the < 3.0 ResourceMetadata system
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ResourceCollectionMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;
    private $resourceCollectionMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $decorated, ResourceCollectionMetadataFactoryInterface $resourceCollectionMetadataFactory)
    {
        $this->decorated = $decorated;
        $this->resourceCollectionMetadataFactory = $resourceCollectionMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        try {
            $resourceMetadata = $this->decorated->create($resourceClass);
        } catch (ResourceClassNotFoundException $e) {
            $resourceMetadataCollection = $this->resourceCollectionMetadataFactory->create($resourceClass);
            // warn about that
            return $resourceMetadataCollection[0];
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }
}
