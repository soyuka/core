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

namespace ApiPlatform\Symfony\State;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
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
            !($request = $context['request'])
            || !($operation instanceof HttpOperation)
            || !($operation->getExtraProperties()['legacy_api_platform_controller'] ?? false)
        ) {
            return $body;
        }

        $controller = $operation->getController();
        if (!$controller || 'api_platform.symfony.main_controller' === $controller) {
            return $body;
        }

        $request->attributes->set('_controller', $controller);

        $controller = $this->resolver->getController($request);
        $arguments = $this->argumentResolver->getArguments($request, $controller);

        return $controller(...$arguments);
    }
}
