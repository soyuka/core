<?php

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\State\ProviderInterface;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

final class ContentNegotiationProvider implements ProviderInterface
{
    /**
     * @param array<string, string[]> $formats
     * @param array<string, string[]> $errorFormats
     */
    public function __construct(private readonly ProviderInterface $inner, private readonly Negotiator $negotiator, private readonly array $formats = [], private readonly array $errorFormats = [])
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!($request = $context['request'] ?? null) || !$operation instanceof HttpOperation) {
            return $this->inner->provide($operation, $uriVariables, $context);
        }

        $isErrorOperation = $operation instanceof ErrorOperation;

        $formats = $operation->getOutputFormats() ?? ($isErrorOperation ? $this->errorFormats : $this->formats);

        $this->addRequestFormats($request, $formats);
        $request->attributes->set('input_format', $this->getInputFormat($operation, $request));

        if (!$isErrorOperation) {
            $request->setRequestFormat($this->getRequestFormat($request, $formats));
        } else {
            $request->setRequestFormat(array_key_first($operation->getOutputFormats()));
        }

        return $this->inner->provide($operation, $uriVariables, $context);
    }

    /**
     * Adds the supported formats to the request.
     *
     * This is necessary for {@see Request::getMimeType} and {@see Request::getMimeTypes} to work.
     * Note that this replaces default mime types configured at {@see Request::initializeFormats}
     *
     * @param array<string, string|string[]> $formats
     */
    private function addRequestFormats(Request $request, array $formats): void
    {
        foreach ($formats as $format => $mimeTypes) {
            $request->setFormat($format, (array) $mimeTypes);
        }
    }

    /**
     * Flattened the list of MIME types.
     *
     * @param array<string, string|string[]> $formats
     *
     * @return array<string, string>
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
     * @param array<string, string|string[]> $formats
     */
    private function getRequestFormat(Request $request, array $formats): string {
        if (($routeFormat = $request->attributes->get('_format') ?: null) && !isset($formats[$routeFormat])) {
            throw new NotFoundHttpException(sprintf('Format "%s" is not supported', $routeFormat));
        }

        if ($routeFormat) {
            $mimeTypes = Request::getMimeTypes($routeFormat);
            $flattenedMimeTypes = $this->flattenMimeTypes([$routeFormat => $mimeTypes]);
        } else {
            $flattenedMimeTypes = $this->flattenMimeTypes($formats);
            $mimeTypes = array_keys($flattenedMimeTypes);
        }

        // First, try to guess the format from the Accept header
        /** @var string|null $accept */
        $accept = $request->headers->get('Accept');
        if (null !== $accept) {
            if (null === $mediaType = $this->negotiator->getBest($accept, $mimeTypes)) {
                throw $this->getNotAcceptableHttpException($accept, $flattenedMimeTypes);
            }

            return $this->getMimeTypeFormat($mediaType->getType(), $formats);
        }

        // Then use the Symfony request format if available and applicable
        $requestFormat = $request->getRequestFormat('') ?: null;
        if (null !== $requestFormat) {
            $mimeType = $request->getMimeType($requestFormat);

            if (isset($flattenedMimeTypes[$mimeType])) {
                return $requestFormat;
            }

            throw $this->getNotAcceptableHttpException($mimeType, $flattenedMimeTypes);
        }

        // Finally, if no Accept header nor Symfony request format is set, return the default format
        return array_key_first($formats);
    }

    /**
     * Gets the format associated with the mime type.
     *
     * Adapted from {@see \Symfony\Component\HttpFoundation\Request::getFormat}.
     *
     * @param array<string, string|string[]> $formats
     */
    private function getMimeTypeFormat(string $mimeType, array $formats): ?string
    {
        $canonicalMimeType = null;
        $pos = strpos($mimeType, ';');
        if (false !== $pos) {
            $canonicalMimeType = trim(substr($mimeType, 0, $pos));
        }

        foreach ($formats as $format => $mimeTypes) {
            if (\in_array($mimeType, $mimeTypes, true)) {
                return $format;
            }
            if (null !== $canonicalMimeType && \in_array($canonicalMimeType, $mimeTypes, true)) {
                return $format;
            }
        }

        return null;
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
    private function getInputFormat(HttpOperation $operation, Request $request): ?string
    {
        /** @var ?string $contentType */
        if (null === ($contentType = $request->headers->get('CONTENT_TYPE'))) {
            return null;
        }

        $formats = $operation->getInputFormats() ?? [];
        if ($format = $this->getMimeTypeFormat($contentType, $formats)) {
            return $format;
        }

        $supportedMimeTypes = [];
        foreach ($formats as $mimeTypes) {
            foreach ($mimeTypes as $mimeType) {
                $supportedMimeTypes[] = $mimeType;
            }
        }

        if (!$request->isMethodSafe() && $request->getMethod() !== 'DELETE') {
            throw new UnsupportedMediaTypeHttpException(sprintf('The content-type "%s" is not supported. Supported MIME types are "%s".', $contentType, implode('", "', $supportedMimeTypes)));
        }

        return null;
    }
}
        // if ($operation instanceof HttpOperation) {
        //     $request->attributes->set('input_format', $this->getInputFormat($operation, $request));
        // }
        // $serverRequest = (new ServerRequest(method: $request->getMethod(), uri: $request->getUri(), body: $request->getContent()))
        //     ->withAttribute('request_format', $requestFormat)
        //     ->withAttribute('input_format', $this->getInputFormat($operation, $request))
        //     ->withAttribute('request_mime_type', $this->errorFormats[$requestFormat][0] ?? $request->getMimeType($requestFormat))
        //     ->withAttribute('previous_data', $request->attributes->get('data'))
        //     ->withAttribute('previous_operation', $request->attributes->get('_api_previous_operation'))
        //
        // ;
