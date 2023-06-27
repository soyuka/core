<?php

namespace ApiPlatform\Symfony\Bundle\SwaggerUi;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\State\ProviderInterface;

/**
 * When an HTML request is sent we provide a swagger ui documentation.
 */
final class SwaggerUiProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface<mixed> $inner
     */
    public function __construct(private readonly ProviderInterface $inner, private readonly OpenApiFactoryInterface $openApiFactory)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $context['request'] ?? null;

        if (!$request || 'html' !== $request->getRequestFormat()) {
            return $this->inner->provide($operation, $uriVariables, $context);
        }

        $request->attributes->set('_api_requested_operation', $operation);
        $operation = new Get(
            class: OpenApi::class,
            processor: 'api_platform.swagger_ui.processor'
        );

        $request->attributes->set('_api_operation', $operation);
        return $this->openApiFactory->__invoke($context);
    }
}
