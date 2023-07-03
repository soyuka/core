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

use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Util\ContentNegotiationTrait;
use Negotiation\Negotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

final class ContentNegotiationProvider implements ProviderInterface
{
    use ContentNegotiationTrait;

    /**
     * @param array<string, string[]> $formats
     * @param array<string, string[]> $errorFormats
     * @param ProviderInterface<mixed> $inner
     */
    public function __construct(private readonly ProviderInterface $inner, Negotiator $negotiator, private readonly array $formats = [], private readonly array $errorFormats = [])
    {
        $this->negotiator = $negotiator;
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
            $request->setRequestFormat($this->getRequestFormat($request, $formats, false));
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

        if (!$request->isMethodSafe() && 'DELETE' !== $request->getMethod()) {
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