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

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Mcp\Metadata\McpResource;
use ApiPlatform\Mcp\Metadata\McpResourceTemplate;
use ApiPlatform\Mcp\Metadata\McpTool;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Creates MCP capability definitions from API Platform operations.
 *
 * @internal
 */
final readonly class McpOperationFactory implements McpCapabilityFactoryInterface
{
    public function __construct(
        private ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private RequestContext $requestContext,
        private SchemaFactoryInterface $schemaFactory,
    ) {
    }

    /**
     * Creates and yields MCP capability definitions.
     *
     * @return \Generator<array{type: string, definition: array}>
     */
    public function create(): \Generator
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);
            foreach ($resourceMetadataCollection as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    if (!$operation instanceof HttpOperation || !($mcp = $operation->getMcp())) {
                        continue;
                    }

                    $capabilities = \is_array($mcp) ? $mcp : [$mcp];

                    foreach ($capabilities as $capability) {
                        if ($capability instanceof McpTool) {
                            yield from $this->buildTool($capability, $operation);
                        } elseif ($capability instanceof McpResource) {
                            yield from $this->buildResource($capability, $operation);
                        } elseif ($capability instanceof McpResourceTemplate) {
                            yield from $this->buildResourceTemplate($capability, $operation);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return \Generator<array{type: string, definition: array}>
     */
    private function buildTool(McpTool $tool, HttpOperation $operation): \Generator
    {
        if (!$tool->name) {
            return;
        }

        yield [
            'type' => 'tool',
            'definition' => [
                'name' => $tool->name,
                'description' => $tool->description,
                'inputSchema' => $tool->inputSchema ?? $this->buildInputSchema($operation),
            ],
        ];
    }

    /**
     * @return \Generator<array{type: string, definition: array}>
     */
    private function buildResource(McpResource $resource, HttpOperation $operation): \Generator
    {
        if (!$operation->getUriTemplate() || !$resource->name) {
            return;
        }

        $uri = \sprintf('%s://%s/%s', $this->requestContext->getScheme(), $this->requestContext->getHost(), ltrim(str_replace('{._format}', '', $operation->getUriTemplate()), '/'));
        $mimeType = current($operation->getInputFormats()['jsonld'] ?? []) ?? 'application/ld+json';

        yield [
            'type' => 'resource',
            'definition' => [
                'uri' => $resource->uri ?? $uri,
                'name' => $resource->name,
                'description' => $resource->description,
                'mimeType' => $resource->mimeType ?? $mimeType,
            ],
        ];
    }

    /**
     * @return \Generator<array{type: string, definition: array}>
     */
    private function buildResourceTemplate(McpResourceTemplate $template, HttpOperation $operation): \Generator
    {
        if (!$operation->getUriTemplate() || !$template->name) {
            return;
        }

        $uri = \sprintf('%s://%s/%s', $this->requestContext->getScheme(), $this->requestContext->getHost(), ltrim(str_replace('{._format}', '', $operation->getUriTemplate()), '/'));
        $mimeType = current($operation->getInputFormats()['jsonld'] ?? []) ?? 'application/ld+json';

        yield [
            'type' => 'resource_template',
            'definition' => [
                'uriTemplate' => $template->uriTemplate ?? $uri,
                'name' => $template->name,
                'description' => $template->description,
                'mimeType' => $template->mimeType ?? $mimeType,
            ],
        ];
    }

    private function buildInputSchema(HttpOperation $operation): ?array
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];

        // 1. Add properties from the request body for relevant methods
        if (\in_array($operation->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            $bodySchema = $this->schemaFactory->buildSchema($operation->getClass(), 'json', Schema::TYPE_INPUT, $operation);
            $rootDefinitionKey = $bodySchema->getRootDefinitionKey();

            if (null !== $rootDefinitionKey && isset($bodySchema->getDefinitions()[$rootDefinitionKey])) {
                $bodyDefinition = $bodySchema->getDefinitions()[$rootDefinitionKey]->getArrayCopy();
                if (isset($bodyDefinition['properties'])) {
                    $schema['properties'] = array_merge($schema['properties'], $bodyDefinition['properties']);
                }
                if (isset($bodyDefinition['required'])) {
                    $schema['required'] = array_merge($schema['required'], $bodyDefinition['required']);
                }
            }
        }

        // 2. Add properties from URI variables
        foreach ($operation->getUriVariables() as $parameterName => $uriVariable) {
            $schema['properties'][$parameterName] = $uriVariable->getSchema() ?? ['type' => 'string'];
            if ($uriVariable->getRequired() ?? true) {
                $schema['required'][] = $parameterName;
            }
        }

        if ('GET' === $operation->getMethod()) {
            foreach ($operation->getParameters() as $parameter) {
                $schema['properties'][$parameter->getKey()] = $parameter->getSchema() ?? ['type' => 'string'];
                if ($parameter->getRequired() ?? true) {
                    $schema['required'][] = $parameter->getKey();
                }
            }
        }

        if ($operation instanceof CollectionOperationInterface) {
            $schema['properties']['pageToken'] = [
                'type' => 'string',
                'description' => 'A token to retrieve the next page of results.',
            ];
        }

        if (empty($schema['properties'])) {
            return null;
        }

        if (empty($schema['required'])) {
            unset($schema['required']);
        } else {
            // Ensure unique values
            $schema['required'] = array_values(array_unique($schema['required']));
        }

        return $schema;
    }
}
