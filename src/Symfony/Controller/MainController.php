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
        $requestFormat = $this->getRequestFormat($operation, $request);

        $uriVariables = [];
        try {
            $uriVariables = $this->getOperationUriVariables($operation, $request->attributes->all(), $operation->getClass());
        } catch (InvalidIdentifierException|InvalidUriVariableException $e) {
            throw new NotFoundHttpException('Invalid identifier value or configuration.', $e);
        }

        $serverRequest = (new ServerRequest(method: $request->getMethod(), uri: $request->getUri(), body: $request->getContent()))
            ->withAttribute('request_format', $requestFormat)
            ->withAttribute('input_format', $this->getInputFormat($operation, $request))
            ->withAttribute('request_mime_type', $this->errorFormats[$requestFormat][0] ?? $request->getMimeType($requestFormat))
            ->withAttribute('previous_data', $request->attributes->get('data'))
            ->withAttribute('previous_operation', $request->attributes->get('_api_previous_operation'))
            ->withAttribute('base_url', $request->getBaseUrl())
            ->withAttribute('id', $request->get('id'))
            ->withAttribute('query_parameters', $request->query->all())
            ->withAttribute('_route', $request->attributes->get('_api_original_route', $request->attributes->get('_route')))
            ->withAttribute('_route_params', $request->attributes->get('_api_original_route_params', $request->attributes->get('_route_params', [])))
        ;

        $context = [
            'request' => &$serverRequest,
            'uri_variables' => $uriVariables,
            'safe_method' => $request->isMethodSafe()
        ];

        $body = $this->provider->provide($operation, $uriVariables, $context);
        return $this->processor->process($body, $operation, $uriVariables, $context);
    }


    private function getRequestFormat(HttpOperation $operation, Request $request) {
        $formats = $operation->getOutputFormats() ?? $this->formats;
        $isErrorOperation = $operation instanceof ErrorOperation;

        $this->addRequestFormats($request, $formats);

        // Empty strings must be converted to null because the Symfony router doesn't support parameter typing before 3.2 (_format)
        if (null === $routeFormat = $request->attributes->get('_format') ?: null) {
            $flattenedMimeTypes = $this->flattenMimeTypes($isErrorOperation ? $this->errorFormats : $formats);
            $mimeTypes = array_keys($flattenedMimeTypes);
        } elseif (!isset($formats[$routeFormat])) {
            if (!$isErrorOperation) {
                throw new NotFoundHttpException(sprintf('Format "%s" is not supported', $routeFormat));
            }

            return $this->getRequestErrorFormat($operation, $request);
        } else {
            $mimeTypes = Request::getMimeTypes($routeFormat);
            $flattenedMimeTypes = $this->flattenMimeTypes([$routeFormat => $mimeTypes]);
        }

        // First, try to guess the format from the Accept header
        /** @var string|null $accept */
        $accept = $request->headers->get('Accept');
        if (null !== $accept) {
            if (null === $mediaType = $this->negotiator->getBest($accept, $mimeTypes)) {
                if (!$isErrorOperation) {
                    throw $this->getNotAcceptableHttpException($accept, $flattenedMimeTypes);
                }

                return $this->getRequestErrorFormat($operation, $request);
            }

            $formatMatcher = new FormatMatcher($formats);
            return $formatMatcher->getFormat($mediaType->getType());
        }

        // Then use the Symfony request format if available and applicable
        $requestFormat = $request->getRequestFormat('') ?: null;
        if (null !== $requestFormat) {
            $mimeType = $request->getMimeType($requestFormat);

            if (isset($flattenedMimeTypes[$mimeType])) {
                return $requestFormat;
            }

            if ($isErrorOperation) {
                return $this->getRequestErrorFormat($operation, $request);
            }

            throw $this->getNotAcceptableHttpException($mimeType, $flattenedMimeTypes);
        }

        // Finally, if no Accept header nor Symfony request format is set, return the default format
        foreach ($formats as $format => $mimeType) {
            return $format;
        }
    }

    /**
     * Adds the supported formats to the request.
     *
     * This is necessary for {@see Request::getMimeType} and {@see Request::getMimeTypes} to work.
     */
    private function addRequestFormats(Request $request, array $formats): void
    {
        foreach ($formats as $format => $mimeTypes) {
            $request->setFormat($format, (array) $mimeTypes);
        }
    }

    /**
     * Retries the flattened list of MIME types.
     */
    private function flattenMimeTypes(array $formats): array
    {
        $flattenedMimeTypes = [];
        foreach ($formats as $format => $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $flattenedMimeTypes[$mimeType] = $format;
            }
        }

        return $flattenedMimeTypes;
    }

    /**
     * Retrieves an instance of NotAcceptableHttpException.
     */
    private function getNotAcceptableHttpException(string $accept, array $mimeTypes): NotAcceptableHttpException
    {
        return new NotAcceptableHttpException(sprintf(
            'Requested format "%s" is not supported. Supported MIME types are "%s".',
            $accept,
            implode('", "', array_keys($mimeTypes))
        ));
    }


    /**
     * Extracts the format from the Content-Type header and check that it is supported.
     *
     * @throws UnsupportedMediaTypeHttpException
     */
    private function getInputFormat(Operation $operation, Request $request): ?string
    {
        $formats = $operation->getInputFormats() ?? [];
        /** @var ?string $contentType */
        $contentType = $request->headers->get('CONTENT_TYPE');
        if (null === $contentType) {
            return null;
            // throw new UnsupportedMediaTypeHttpException('The "Content-Type" header must exist.');
        }

        $formatMatcher = new FormatMatcher($formats);
        $format = $formatMatcher->getFormat($contentType);
        if (null === $format) {
            $supportedMimeTypes = [];
            foreach ($formats as $mimeTypes) {
                foreach ($mimeTypes as $mimeType) {
                    $supportedMimeTypes[] = $mimeType;
                }
            }

            return null;
            // throw new UnsupportedMediaTypeHttpException(sprintf('The content-type "%s" is not supported. Supported MIME types are "%s".', $contentType, implode('", "', $supportedMimeTypes)));
        }

        return $format;
    }

    public function getRequestErrorFormat(?HttpOperation $operation, Request $request): string
    {
        $errorResourceFormats = array_merge($operation?->getOutputFormats() ?? [], $operation?->getFormats() ?? [], $this->errorFormats);

        $flattened = $this->flattenMimeTypes($errorResourceFormats);
        if ($flattened[$accept = $request->headers->get('Accept')] ?? false) {
            return $flattened[$accept];
        }

        if (isset($errorResourceFormats['jsonproblem'])) {
            return 'jsonproblem';
        }

        return array_key_first($errorResourceFormats);
    }
}
