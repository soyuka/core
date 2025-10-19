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

namespace ApiPlatform\Mcp\Factory;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Creates MCP capabilities from the Hydra API documentation.
 *
 * @internal
 */
final readonly class McpDocumentationFactory implements McpCapabilityFactoryInterface
{
    public function __construct(
        private ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private RequestContext $requestContext,
        private string $invokeOperationToolName = 'invoke_hydra_operation',
    ) {
    }

    /**
     * @return \Generator<array{type: string, definition: array}>
     */
    public function create(): \Generator
    {
        $prefix = sprintf('%s://%s', $this->requestContext->getScheme(), $this->requestContext->getHost());

        // Static Resources
        yield from $this->createStaticResources($prefix);

        // Discovered Collection Resources
        yield from $this->createCollectionResources($prefix);

        // Generic tool for invoking operations
        yield [
            'type' => 'tool',
            'definition' => [
                'name' => $this->invokeOperationToolName,
                'description' => 'Executes a specific state-dependent Hydra API operation (like POST, PUT, DELETE) that was discovered within a Hydra resource\'s content.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'uri' => [
                            'type' => 'string',
                            'description' => 'The target URI for the operation (from the resource or the operation itself).',
                        ],
                        'method' => [
                            'type' => 'string',
                            'description' => 'The HTTP method (e.g., \'POST\', \'PUT\', \'DELETE\') from the hydra:method property.',
                        ],
                        'payload' => [
                            'type' => 'object',
                            'description' => 'An optional JSON payload body for POST or PUT requests. Must conform to the operation\'s hydra:expects schema.',
                            'nullable' => true,
                        ],
                    ],
                    'required' => ['uri', 'method'],
                ],
            ],
        ];
    }

    private function createStaticResources(string $prefix): \Generator
    {
        yield [
            'type' => 'resource',
            'definition' => [
                'uri' => $prefix.'/docs.jsonld',
                'name' => 'hydra_docs',
                'description' => 'The Hydra documentation for this API.',
                'mimeType' => 'application/ld+json',
            ],
        ];
        yield [
            'type' => 'resource',
            'definition' => [
                'uri' => $prefix.'/index',
                'name' => 'api_entrypoint',
                'description' => 'The main entrypoint for the API.',
                'mimeType' => 'application/ld+json',
            ],
        ];
    }

    private function createCollectionResources(string $prefix): \Generator
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);
            foreach ($resourceMetadataCollection as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    if ($operation instanceof GetCollection && $operation instanceof HttpOperation && $operation->getUriTemplate()) {
                        yield [
                            'type' => 'resource',
                            'definition' => [
                                'uri' => $prefix.(str_replace('{._format}', '', $operation->getUriTemplate())),
                                'name' => $resource->getShortName().'_collection',
                                'description' => $resource->getDescription() ?? 'Collection of '.$resource->getShortName(),
                                'mimeType' => 'application/ld+json',
                            ],
                        ];
                    }
                }
            }
        }
    }
}
