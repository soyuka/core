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

namespace ApiPlatform\State\ParameterProvider;

use ApiPlatform\Metadata\Exception\InvalidIdentifierException;
use ApiPlatform\Metadata\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Checks if the linked resources have security attributes and prepares them for access checking.
 *
 * @experimental
 */
final class ReadLinkParameterProvider implements ParameterProviderInterface
{
    /**
     * @param ProviderInterface<mixed> $locator
     */
    public function __construct(
        private readonly ProviderInterface $locator,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
    ) {
    }

    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        $operation = $context['operation'];
        $extraProperties = $parameter->getExtraProperties();

        if ($parameter instanceof Link) {
            $linkClass = $parameter->getFromClass() ?? $parameter->getToClass();
            $securityObjectName = $parameter->getSecurityObjectName() ?? $parameter->getToProperty() ?? $parameter->getFromProperty();
        }

        $securityObjectName ??= $parameter->getKey();

        $linkClass ??= $extraProperties['resource_class'] ?? $operation->getClass();

        if (!$linkClass) {
            return $operation;
        }

        $linkOperation = $this->resourceMetadataCollectionFactory
            ->create($linkClass)
            ->getOperation($operation->getExtraProperties()['parent_uri_template'] ?? $extraProperties['uri_template'] ?? null);

        try {
            $relation = $this->locator->provide($linkOperation, [$parameter->getKey() => $parameter->getValue()], $context);
        } catch (ProviderNotFoundException) {
            $relation = null;
        }

        $parameter->setValue($relation);

        if (!$relation && true === ($extraProperties['throw_not_found'] ?? true)) {
            throw new NotFoundHttpException('Relation for link security not found.');
        }

        $context['request']?->attributes->set($securityObjectName, $relation);

        return $operation;
    }
}
