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
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\UriVariablesResolverTrait;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use Nyholm\Psr7\ServerRequest;
use Symfony\Component\HttpFoundation\Request;
use ApiPlatform\Api\FormatMatcher;
use Negotiation\Negotiator;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class MainController
{
    use OperationRequestInitiatorTrait;
    use UriVariablesResolverTrait;

    public function __construct(
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly ProviderInterface $provider,
        private readonly ProcessorInterface $processor,
        private readonly Negotiator $negotiator,
        private readonly array $formats = [],
        private readonly array $errorFormats = [],
        ?UriVariablesConverterInterface $uriVariablesConverter = null,
    )
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->uriVariablesConverter = $uriVariablesConverter;
    }

    public function __invoke(Request $request)
    {
        $operation = $this->initializeOperation($request);
        $uriVariables = [];
        try {
            $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
        } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
            throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
        }

        $context = [
            'request' => &$request,
            'uri_variables' => $uriVariables
        ];

        $body = $this->provider->provide($operation, $uriVariables, $context);
        return $this->processor->process($body, $operation, $uriVariables, $context);
    }

}
