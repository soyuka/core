<?php

namespace ApiPlatform\Symfony\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\T;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * Until API Platform 3.2, Symfony listeners are called and we altered the request/response.
 * In API Platform 4 a controller will skip anything API-platform related, unless explicitly said
 * using Providers/Processors composition.
 * For compatibility reasons we will call the controller manually in this backward compatibility layer.
 *
 * @internal
 */
final class ControllerProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $inner, private readonly ArgumentResolverInterface $argumentResolver, private readonly ControllerResolverInterface $resolver)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $body = $this->inner->provide($operation, $uriVariables, $context);

        if (
            !($request = $context['request']) ||
            !($controller = $operation->getExtraProperties()['legacy_api_platform_controller'] ?? null)
        ) {
            return $body;
        }

        $request->attributes->set('_controller', $controller);

        $controller = $this->resolver->getController($request);
        $arguments = $this->argumentResolver->getArguments($request, $controller);

        return $controller(...$arguments);
    }
}
