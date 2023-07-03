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

namespace ApiPlatform\Symfony\Controller;

use ApiPlatform\Api\UriVariablesConverterInterface;
use ApiPlatform\Exception\InvalidIdentifierException;
use ApiPlatform\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MainController
{
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    /**
     * @param ProviderInterface<mixed> $provider
     */
    public function __construct(
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly ProviderInterface $provider,
        private readonly ProcessorInterface $processor,
        UriVariablesConverterInterface $uriVariablesConverter = null,
    ) {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->uriVariablesConverter = $uriVariablesConverter;
    }

    public function __invoke(Request $request): Response
    {
        $operation = $this->initializeOperation($request);
        $uriVariables = [];
        try {
            $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
        } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
            throw new NotFoundHttpException('Invalid uri variables.', $e);
        }

        $context = [
            'request' => &$request,
            'uri_variables' => $uriVariables,
            'resource_class' => $operation->getClass()
        ];

        $body = $this->provider->provide($operation, $uriVariables, $context);

        // The provider can change the Operation so we extract it again
        if ($request->attributes->get('_api_operation') !== $operation) {
            $operation = $this->initializeOperation($request);
            try {
                $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
            } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
            }
        }

        $context['previous_data'] = $request->attributes->get('previous_data');
        $context['data'] = $request->attributes->get('data');

        if (null === $operation->canWrite()) {
            $operation = $operation->withWrite(!$request->isMethodSafe());
        }

        return $this->processor->process($body, $operation, $uriVariables, $context);
    }
}
