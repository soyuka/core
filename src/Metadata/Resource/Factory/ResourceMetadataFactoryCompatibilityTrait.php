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

trait ResourceMetadataFactoryCompatibilityTrait 
{
    /**
     * @param array|ResourceMetadata $resourceMetadata
     * @return ResourceMetadata[]
     */
    public function getMetadataAsArray($resourceMetadata): array
    {
        if (!is_array($resourceMetadata)) {
            // deprecation
            return [$resourceMetadata];
        }

        return $resourceMetadata;
    }

}
