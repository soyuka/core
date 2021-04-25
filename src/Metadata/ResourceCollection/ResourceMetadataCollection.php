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

namespace ApiPlatform\Core\Metadata\ResourceCollection;

use ApiPlatform\Metadata\Resource;

/**
 * @extends \ArrayObject<int, Resource>
 */
final class ResourceMetadataCollection extends \ArrayObject
{
    private array $metadataCache = [];
    private array $operationCache = [];

    public function getOperation(string $method, string $uriTemplate)
    {
        if (isset($this->operationCache[$uriTemplate][$method])) {
            return $this->operationCache[$uriTemplate][$method];
        }

        if (!($resourceMetadata = $this->getResourceMetadata($uriTemplate))) {
            return null;
        }

        foreach ($resourceMetadata->operations as $operation) {
            if ($operation->method === $method) {
                return isset($this->operationCache[$uriTemplate]) ? $this->operationCache[$uriTemplate][$method] = $operation : $this->operationCache[$uriTemplate] = [$method => $operation];
            }
        }

        return null;
    }

    public function getResourceMetadata(string $uriTemplate): ?Resource
    {
        if (isset($this->metadataCache[$uriTemplate])) {
            return $this->metadataCache[$uriTemplate];
        }

        $it = $this->getIterator();

        while ($it->valid()) {
            /** @var resource */
            $metadata = $it->current();

            if ($metadata->uriTemplate === $uriTemplate) {
                $this->metadataCache[$uriTemplate] = $metadata;

                return $metadata;
            }

            $it->next();
        }

        return null;
    }
}
