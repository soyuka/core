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

namespace ApiPlatform\Mcp\Server\Handler\Request;

use ApiPlatform\Mcp\Schema\Result\StructuredContentResult;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request as McpRequest;
use Mcp\Schema\JsonRpc\Response as McpResponse;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;

final class CallToolHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly OperationMetadataFactoryInterface $mcpOperationMetadataFactory,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function supports(McpRequest $request): bool
    {
        return $request instanceof CallToolRequest;
    }

    public function handle(McpRequest $request, SessionInterface $session): McpResponse|Error
    {
        \assert($request instanceof CallToolRequest);

        $toolName = $request->name;
        $arguments = $request->arguments ?? [];

        $this->logger->debug('Executing tool', ['name' => $toolName, 'arguments' => $arguments]);

        try {
            $result = $this->executeTool($toolName, $arguments);

            if ($result instanceof StructuredContentResult) {
                return new McpResponse($request->getId(), $result);
            }

            $rawResult = new CallToolResult([new TextContent($result)]);

            return new McpResponse($request->getId(), $result ? new StructuredContentResult(json_decode($result, true), $rawResult) : $rawResult);
        } catch (OperationNotFoundException $e) {
            $this->logger->error('Tool not found', ['name' => $toolName, 'exception' => $e->getMessage()]);

            return new Error($request->getId(), Error::METHOD_NOT_FOUND, $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Unhandled error during tool execution', [
                'name' => $toolName,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Error::forInternalError('Error while executing tool', $request->getId());
        }
    }

    private function executeTool(string $mcpName, array $arguments): ?string
    {
        $operation = $this->mcpOperationMetadataFactory->create($mcpName);
        if (!$operation || !$operation instanceof HttpOperation) {
            throw new OperationNotFoundException(\sprintf('Operation with MCP name "%s" not found.', $mcpName));
        }

        $uriVariables = [];
        $bodyParams = [];
        $queryParams = [];
        $pageToken = null;

        foreach ($arguments as $key => $value) {
            if (\array_key_exists($key, $operation->getUriVariables() ?? [])) {
                $uriVariables[$key] = $value;
            } elseif ('pageToken' === $key) {
                $pageToken = $value;
            } elseif ('GET' === $operation->getMethod()) {
                $queryParams[$key] = $value;
            } else {
                $bodyParams[$key] = $value;
            }
        }

        $url = $pageToken ? base64_decode($pageToken) : $this->router->generate($operation->getRouteName() ?? $operation->getName(), $uriVariables);
        $method = $operation->getMethod();
        $content = $bodyParams ? json_encode($bodyParams, \JSON_THROW_ON_ERROR) : null;
        $accept = current($operation->getOutputFormats())[0] ?? 'application/json';

        $subRequest = HttpRequest::create($url, $method, $queryParams, [], [], ['HTTP_ACCEPT' => $accept], $content);
        if ($content) {
            $contentType = current($operation->getInputFormats())[0] ?? 'application/json';
            $subRequest->headers->set('Content-Type', $contentType);
        }

        $response = $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        if (HttpResponse::HTTP_NO_CONTENT === $response->getStatusCode()) {
            return null;
        }

        $json = json_decode($response->getContent(), true);
        if ($operation instanceof CollectionOperationInterface) {
            $result = ['items' => $json['hydra:member'] ?? $json['member']];
            if ($next = ($json['hydra:view']['hydra:next'] ?? $json['view']['next'])) {
                $result['nextPageToken'] = base64_encode($next);
            }

            return new StructuredContentResult($result);
        }

        return $response->getContent();
    }
}
