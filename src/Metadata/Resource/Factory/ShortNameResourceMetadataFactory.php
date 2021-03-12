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

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * Guesses the short name from the class name if not already set.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ShortNameResourceMetadataFactory implements ResourceCollectionMetadataFactoryInterface 
{
    use ResourceMetadataFactoryCompatibilityTrait;

    private $decorated;

    public function __construct(ResourceMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): array
    {
        $resourceMetadataCollection = $this->getMetadataAsArray($this->decorated->create($resourceClass));

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            if (null !== $resourceMetadata->getShortName()) {
                continue;
            }

            if (false !== $pos = strrpos($resourceClass, '\\')) {
                $resourceMetadataCollection[$key] = $resourceMetadata->withShortName(substr($resourceClass, $pos + 1));
                continue;
            }

            $resourceMetadataCollection[$key] = $resourceMetadata->withShortName($resourceClass);
        }

        return $resourceMetadataCollection;
    }
}
