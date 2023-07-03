<?php

namespace ApiPlatform\Symfony\Bundle\SwaggerUi;

use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\State\ProviderInterface;

/**
 * When an HTML request is sent we provide a swagger ui documentation.
 * @internal
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
        if (
            !($operation instanceof HttpOperation)
            || !($request = $context['request'] ?? null)
            || 'html' !== $request->getRequestFormat()
        ) {
            return $this->inner->provide($operation, $uriVariables, $context);
        }

        if (!$request->attributes->has('_api_requested_operation')) {
            $request->attributes->set('_api_requested_operation', $operation);
        }

        $swaggerUiOperation  = new Get(
            class: OpenApi::class,
            processor: 'api_platform.swagger_ui.processor',
            validate: false,
            read: false,
            status: $operation->getStatus()
        );

        $body = $this->inner->provide($swaggerUiOperation, $uriVariables, $context);

        // save our operation
        $request->attributes->set('_api_operation', $swaggerUiOperation);

        return $this->openApiFactory->__invoke($context);
    }
}
