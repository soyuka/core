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
        if ($operation->getClass() === OpenApi::class) {
            return $this->inner->provide($operation, $uriVariables, $context);
        }

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

        // We need to call our operation provider just in case it fails
        // when it fails we'll get an Error and we'll fix the status accordingly
        // @see features/main/content_negotiation.feature:119
        if (!$operation instanceof Error) {
            $this->inner->provide($operation, $uriVariables, $context);
        }

        $swaggerUiOperation  = new Get(
            class: OpenApi::class,
            processor: 'api_platform.swagger_ui.processor',
            validate: false,
            read: false,
            write: true, // force write so that our processor gets called
            status: $operation->getStatus()
        );

        // save our operation
        $request->attributes->set('_api_operation', $swaggerUiOperation);
        return $this->openApiFactory->__invoke($context);
    }
}
