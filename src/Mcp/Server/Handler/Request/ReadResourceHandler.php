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

use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Mcp\Schema\Result\StructuredContentResult;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\ReadResourceRequest;
use Mcp\Schema\Result\ReadResourceResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ReadResourceHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly NormalizerInterface $normalizer,
        private readonly string $title = '',
        private readonly string $description = '',
        private readonly string $version = '',
        private readonly ?LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request instanceof ReadResourceRequest;
    }

    public function handle(Request $request, SessionInterface $session): Response|Error
    {
        assert($request instanceof ReadResourceRequest);

        $uri = $request->uri;

        $this->logger->debug('Reading resource', ['uri' => $uri]);

        try {
            // Special handling for docs
            if (str_ends_with($uri, '/docs.jsonld')) {
                $documentation = new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version);
                $data = $this->normalizer->normalize($documentation, 'jsonld');
                $content = json_encode($data);
                $textResourceContents = new TextResourceContents($uri, 'application/ld+json', $content);

                return new Response($request->getId(), new StructuredContentResult($data, new ReadResourceResult([$textResourceContents])));
            }

            // Special handling for entrypoint
            if (str_ends_with($uri, '/api') || str_ends_with($uri, '/entrypoint')) { // Support both
                $entrypoint = new Entrypoint($this->resourceNameCollectionFactory->create());
                $data = $this->normalizer->normalize($entrypoint, 'jsonld');
                $content = json_encode($data);
                $textResourceContents = new TextResourceContents($uri, 'application/ld+json', $content);

                return new Response($request->getId(), new StructuredContentResult($data, new ReadResourceResult([$textResourceContents])));
            }

            $httpRequest = HttpRequest::create($uri, 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json, application/ld+json, application/vnd.openapi+json']);
            $response = $this->kernel->handle($httpRequest, HttpKernelInterface::SUB_REQUEST);
            if (!$content = $response->getContent()) {
                throw new RuntimeException('No content');
            }

            $array = json_decode($content, true);

            if (isset($array['hydra:search']) && 'hydra:IriTemplate' === ($array['hydra:search']['@type'] ?? null)) {
                $template = $array['hydra:search'];
                $mappings = $template['hydra:mapping'];
                $pathParts = explode('/', trim(parse_url($uri, \PHP_URL_PATH), '/'));
                $resourceName = end($pathParts);
                $toolName = 'search_'.$resourceName;

                $inputSchema = ['type' => 'object', 'properties' => []];
                foreach ($mappings as $mapping) {
                    $propName = $mapping['hydra:property'];
                    $inputSchema['properties'][$propName] = ['type' => 'string', 'description' => $mapping['hydra:description'] ?? 'A search query for '.$propName];
                    if ($mapping['hydra:required'] ?? false) {
                        $inputSchema['required'][] = $propName;
                    }
                }

                $newTool = [
                    'name' => $toolName,
                    'description' => $template['hydra:title'] ?? 'Searches the collection of '.$resourceName.' resources.',
                    'inputSchema' => $inputSchema,
                    'hydra_template' => $template['hydra:template'],
                ];

                $dynamicTools = $session->get('dynamic_tools', []);
                $dynamicTools[$toolName] = $newTool;
                $session->set('dynamic_tools', $dynamicTools);

                if (method_exists($session, 'notify')) {
                    $session->notify('notifications/tools/list_changed', []);
                }
            }

            if (isset($array['@id'])) {
                $textResourceContents = new TextResourceContents($array['@id'], 'application/ld+json', $content);
            } else {
                $textResourceContents = new TextResourceContents($uri, $response->headers->get('content-type')[0], $content);
            }

            return new Response($request->getId(), new StructuredContentResult($array, new ReadResourceResult([$textResourceContents])));
        } catch (OperationNotFoundException $e) {
            $this->logger->error('Resource not found', ['uri' => $uri]);

            return new Error($request->getId(), Error::RESOURCE_NOT_FOUND, $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Error while reading resource "%s": "%s".', $uri, $e->getMessage()));

            return Error::forInternalError('Error while reading resource', $request->getId());
        }
    }
}
