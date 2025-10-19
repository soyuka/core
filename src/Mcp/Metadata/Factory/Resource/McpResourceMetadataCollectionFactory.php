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

namespace ApiPlatform\Mcp\Metadata\Factory\Resource;

use ApiPlatform\Mcp\Metadata\McpCapabilityInterface;
use ApiPlatform\Mcp\Metadata\McpResource;
use ApiPlatform\Mcp\Metadata\McpResourceTemplate;
use ApiPlatform\Mcp\Metadata\McpTool;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Adds a sanitized, MCP-compliant name to each operation's metadata.
 *
 * @internal
 */
final readonly class McpResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resource) {
            if (!$operations = $resource->getOperations()) {
                continue;
            }

            $newOperations = new Operations();
            if (!$resource->getMcp()) {
                continue;
            }

            foreach ($operations as $operationName => $operation) {
                if (!$operation instanceof HttpOperation || !($mcp = $operation->getMcp())) {
                    $newOperations->add($operationName, $operation);
                    continue;
                }

                $capabilities = [];
                if (\is_array($mcp)) {
                    $capabilities = $mcp;
                } else if (true === $mcp) {
                    $capabilities[] = new McpTool();
                } else {
                    $capabilities[] = $mcp;
                }

                $completedCapabilities = [];
                foreach ($capabilities as $capability) {
                    if (!$capability instanceof McpCapabilityInterface) {
                        continue;
                    }

                    $completedCapabilities[] = $this->completeCapability($capability, $operation);
                }

                if ($completedCapabilities) {
                    $operation = $operation->withMcp($completedCapabilities);
                }

                $newOperations->add($operationName, $operation);
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($newOperations);
        }

        return $resourceMetadataCollection;
    }

    private function completeCapability(McpCapabilityInterface $capability, HttpOperation $operation): McpCapabilityInterface
    {
        if ($capability instanceof McpTool) {
            $capability->name ??= $this->getMcpName($operation);
            $capability->description ??= $operation->getDescription();
        }

        if ($capability instanceof McpResource) {
            $capability->name ??= $this->getMcpName($operation);
            $capability->description ??= $operation->getDescription();
        }

        if ($capability instanceof McpResourceTemplate) {
            $capability->name ??= $this->getMcpName($operation);
            $capability->description ??= $operation->getDescription();
        }

        return $capability;
    }

    private function getMcpName(HttpOperation $operation): string
    {
        return strtolower($operation->getShortName()).
            '_'.$this->getHttpMethodName($operation->getMethod()).
            ($operation instanceof CollectionOperationInterface ? '_list' : '').
            ($operation->getUriVariables() ? '_by_'.implode('_', array_keys($operation->getUriVariables())) : '');
    }

    private function getHttpMethodName(?string $method): string
    {
        return match (strtolower($method ?? '')) {
            'post' => 'create',
            'put' => 'upsert',
            'patch' => 'update',
            'delete' => 'delete',
            default => 'retrieve',
        };
    }
}
