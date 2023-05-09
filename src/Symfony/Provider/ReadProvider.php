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

namespace ApiPlatform\Symfony\Provider;

use ApiPlatform\Api\UriVariablesConverterInterface;
use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\Util\CloneTrait;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use ApiPlatform\Util\RequestParser;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Retrieves data from the applicable data provider and sets it as a request parameter called data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ReadProvider implements ProviderInterface
{
    use CloneTrait;
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    public function __construct(
        private readonly ProviderInterface $provider,
        private readonly ?SerializerContextBuilderInterface $serializerContextBuilder = null,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // if (null === $filters = $operation->getFilters()) {
        //     $queryString = RequestParser::getQueryString($request);
        //     $filters = $queryString ? RequestParser::parseRequestParams($queryString) : null;
        // }
        //
        // if ($filters) {
        //     $context['filters'] = $filters;
        // }
        if (!$operation instanceof HttpOperation && !($operation->canRead() ?? true) || !$operation->getUriVariables()) {
            // not sure we should call something here...
            return $this->provider->provide($operation, $uriVariables, $context);
        }

        try {
            $data = $this->provider->provide($operation, $uriVariables, $context);
        } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
            throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
        }

        if (
            null === $data
            && (
                'PUT' !== $operation->getMethod()
                || ($operation instanceof Put && !($operation->getAllowCreate() ?? false))
            )
        ) {
            throw new NotFoundHttpException('Not Found');
        }

        return $data;
    }
}
