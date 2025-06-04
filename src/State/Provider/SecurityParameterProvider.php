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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\UriVariableParameterInterface;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\ParameterParserTrait;
use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Loops over parameters to check parameter security.
 * Throws an exception if security is not granted.
 *
 * @experimental
 *
 * @implements ProviderInterface<object>
 */
final class SecurityParameterProvider implements ProviderInterface
{
    use ParameterParserTrait;

    /**
     * @param ProviderInterface<object> $decorated
     */
    public function __construct(private readonly ProviderInterface $decorated, private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $body = $this->decorated->provide($operation, $uriVariables, $context);
        $request = $context['request'] ?? null;

        $operation = $request?->attributes->get('_api_operation') ?? $operation;
        foreach ($operation->getParameters() ?? [] as $parameter) {
            $extraProperties = $parameter->getExtraProperties();
            if (null === $security = $parameter->getSecurity()) {
                continue;
            }

            if (($v = $parameter->getValue()) instanceof ParameterNotFound) {
                continue;
            }

            if ($parameter instanceof Link) {
                $targetResource = $parameter->getFromClass() ?? $parameter->getToClass() ?? null;
            }

            $targetResource ??= $extraProperties['resource_class'] ?? $context['resource_class'] ?? null;

            if (!$targetResource) {
                continue;
            }

            if ($parameter instanceof Link) {
                $securityObjectName = $parameter->getSecurityObjectName() ?? $parameter->getToProperty() ?? $parameter->getFromProperty() ?? null;
            }

            $securityObjectName ??= $parameter->getKey();

            $securityContext = [
                $parameter->getKey() => $v,
                'object' => $body,
                'operation' => $operation,
                'previous_object' => $request?->attributes->get('previous_data'),
                'request' => $request,
                $securityObjectName => $request?->attributes->get($securityObjectName),
            ];

            if (!$this->resourceAccessChecker->isGranted($targetResource, $security, $securityContext)) {
                throw $operation instanceof GraphQlOperation ? new AccessDeniedHttpException($parameter->getSecurityMessage() ?? 'Access Denied.') : new AccessDeniedException($parameter->getSecurityMessage() ?? 'Access Denied.');
            }
        }

        return $body;
    }
}
