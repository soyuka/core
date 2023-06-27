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

namespace ApiPlatform\Documentation\Action;

use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Documentation\DocumentationInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generates the API documentation.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class DocumentationAction
{
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly string $title = '',
        private readonly string $description = '',
        private readonly string $version = '',
        private readonly ?OpenApiFactoryInterface $openApiFactory = null,
        private readonly ?ProviderInterface $provider = null,
        private readonly ?ProcessorInterface $processor = null
    )
    {
    }

    /**
     * @return DocumentationInterface|OpenApi
     */
    public function __invoke(Request $request = null)
    {
        $context = [];
        if (null !== $request) {
            $context['request'] = &$request;
            $context['base_url'] = $request->getBaseUrl();
            $request->attributes->set('_api_normalization_context', $request->attributes->get('_api_normalization_context', []) + $context);
            if ($request->query->getBoolean('api_gateway')) {
                $context['api_gateway'] = true;
            }

            $htmlPrefered = 'html' === $request->getPreferredFormat();

            if (('json' === $request->getRequestFormat() || $htmlPrefered) && null !== $this->openApiFactory) {
                if ($this->provider && $this->processor) {
                    $operation = new Get(class: OpenApi::class, provider: fn() => $this->openApiFactory->__invoke($context));
                    if ($htmlPrefered) {
                        $operation = $operation->withProcessor('api_platform.swagger_ui.processor');
                    }

                    $request->attributes->set('_api_operation', $operation);
                    $body = $this->provider->provide($operation, [], $context);
                    return $this->processor->process($body, $operation, [], $context);
                }

                return $this->openApiFactory->__invoke($context);
            }
        }

        if ($this->provider && $this->processor) {
            $operation = new Get(class: Documentation::class, provider: fn() => new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version));
            $request->attributes->set('_api_operation', $operation);
            $body = $this->provider->provide($operation, [], $context);
            return $this->processor->process($body, $operation, [], $context);
        }

        return new Documentation($this->resourceNameCollectionFactory->create(), $this->title, $this->description, $this->version);
    }
}
