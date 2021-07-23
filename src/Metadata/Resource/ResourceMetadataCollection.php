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

namespace ApiPlatform\Metadata\Resource;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;

/**
 * @experimental
 * @extends \ArrayObject<int, ApiResource>
 */
final class ResourceMetadataCollection extends \ArrayObject
{
    private array $operationCache = [];
    private string $resourceClass;

    public function __construct(string $resourceClass)
    {
        $this->resourceClass = $resourceClass;
        parent::__construct();
    }

    public function getOperation(?string $operationName = null, bool $forceCollection = false): Operation
    {
        if (isset($this->operationCache[$operationName ?? ''])) {
            return $this->operationCache[$operationName ?? ''];
        }

        $it = $this->getIterator();
        $metadata = null;

        while ($it->valid()) {
            /** @var resource */
            $metadata = $it->current();

            foreach ($metadata->getOperations() as $name => $operation) {
                if (null === $operationName && \in_array($operation->getMethod(), [Operation::METHOD_GET, Operation::METHOD_OPTIONS, Operation::METHOD_HEAD], true) && ($forceCollection ? $operation->isCollection() : !$operation->isCollection())) {
                    return $this->operationCache[''] = $operation;
                }

                if ($name === $operationName) {
                    return $this->operationCache[$operationName] = $operation;
                }
            }

            $it->next();
        }

        // Hide the FQDN in the exception message if possible
        $shortName = $metadata ? $metadata->getShortName() : $this->resourceClass;
        if (!$metadata) {
            if (false !== $pos = strrpos($shortName, '\\')) {
                $shortName = substr($shortName, $pos + 1);
            }
        }

        throw new OperationNotFoundException(sprintf('Operation "%s" not found for resource "%s".', $operationName, $shortName));
    }
}
