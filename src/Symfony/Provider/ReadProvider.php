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
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\Exception\ProviderNotFoundException;
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

        if (!$operation instanceof HttpOperation) {
            return null;
        }

        if (!($operation->canRead() ?? true) || (!$operation->getUriVariables() && !($context['safe_method'] ?? false))) {
            return null;
        }


        try {
            $data = $this->provider->provide($operation, $uriVariables, $context);
            if (isset($context['request'])) {
                $context['request'] = $context['request']->withAttribute('previous_data', $data);
            }
        } catch (ProviderNotFoundException $e) {
            $data = null;
        }


        if (
            null === $data
            && 'POST' !== $operation->getMethod()
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

