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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\ApiResource\Error;
use ApiPlatform\Metadata\Error as ErrorOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use ApiPlatform\Validator\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\ErrorListener as SymfonyErrorListener;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;

/**
 * This error listener extends the Symfony one in order to add
 * the `_api_operation` attribute when the request is duplicated.
 * It will later be used to retrieve the exceptionToStatus from the operation ({@see ExceptionAction}).
 */
final class ErrorListener extends SymfonyErrorListener
{
    use OperationRequestInitiatorTrait;

    public function __construct(
        object|array|string|null $controller,
        LoggerInterface $logger = null,
        bool $debug = false,
        array $exceptionsMapping = [],
        ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        private readonly array $errorFormats = [],
        private readonly array $exceptionToStatus = [],
        private readonly ?IdentifiersExtractorInterface $identifiersExtractor = null,
        private readonly ?ResourceClassResolverInterface $resourceClassResolver = null
    ) {
        parent::__construct($controller, $logger, $debug, $exceptionsMapping);
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    protected function duplicateRequest(\Throwable $exception, Request $request): Request
    {
        $dup = parent::duplicateRequest($exception, $request);
        $apiOperation = $this->initializeOperation($request);
        $format = $this->getErrorFormat($request, $apiOperation instanceof HttpOperation ? $apiOperation->getOutputFormats() : [], $this->errorFormats);

        if ($this->resourceClassResolver?->isResourceClass($exception::class)) {
            $resourceCollection = $this->resourceMetadataCollectionFactory->create($exception::class);

            $operation = null;
            foreach ($resourceCollection as $resource) {
                foreach ($resource->getOperations() as $op) {
                    foreach ($op->getOutputFormats() as $key => $value) {
                        if ($key === $format) {
                            $operation = $op;
                            break 3;
                        }
                    }
                }
            }

            // No operation found for the requested format, we take the first available
            if (!$operation) {
                $operation = $resourceCollection->getOperation();
            }
            $errorResource = $exception;
        } elseif ($this->resourceMetadataCollectionFactory) {
            // Create a generic, rfc7807 compatible error according to the wanted format
            /** @var HttpOperation $operation */
            $operation = $this->resourceMetadataCollectionFactory->create(Error::class)->getOperation($this->getFormatOperation($format ?? null));
            $operation = $operation->withStatus($this->getStatusCode($apiOperation, $request, $operation, $exception));
            $errorResource = Error::createFromException($exception, $operation->getStatus());
        } else {
            /** @var HttpOperation $operation */
            $operation = new ErrorOperation(name: '_api_errors_problem', class: Error::class, outputFormats: ['jsonld' => ['application/ld+json']], normalizationContext: ['groups' => ['jsonld'], 'skip_null_values' => true]);
            $operation = $operation->withStatus($this->getStatusCode($apiOperation, $request, $operation, $exception));
            $errorResource = Error::createFromException($exception, $operation->getStatus());
        }

        if (!$operation->getProvider()) {
            $operation = $operation->withProvider(provider: fn () => 'jsonapi' === $format && $errorResource instanceof ConstraintViolationListAwareExceptionInterface ? $errorResource->getConstraintViolationList() : $errorResource);
        }

        $identifiers = $this->identifiersExtractor?->getIdentifiersFromItem($errorResource, $operation) ?? [];

        if ($exception instanceof ValidationException) {
            if (!($apiOperation?->getExtraProperties()['rfc_7807_compliant_errors'] ?? false)) {
                $operation = $operation->withNormalizationContext([
                    'groups' => ['legacy_'.$format],
                    'force_iri_generation' => false,
                ]);
            }
        }

        // $dup->attributes->set('_api_error', true);
        $dup->attributes->set('_api_resource_class', $operation->getClass());
        $dup->attributes->set('_api_previous_operation', $apiOperation);
        $dup->attributes->set('_api_operation', $operation);
        $dup->attributes->set('_api_operation_name', $operation->getName());
        $dup->attributes->remove('exception');
        // $dup->attributes->set('data', $errorResource);
        $dup->attributes->set('_api_original_route', $request->attributes->get('_route'));
        $dup->attributes->set('_api_original_route_params', $request->attributes->get('_route_params'));

        foreach ($identifiers as $name => $value) {
            $dup->attributes->set($name, $value);
        }

        return $dup;
    }

    private function getOperationExceptionToStatus(Request $request): array
    {
        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if ([] === $attributes) {
            return [];
        }

        $resourceMetadataCollection = $this->resourceMetadataCollectionFactory->create($attributes['resource_class']);
        /** @var HttpOperation $operation */
        $operation = $resourceMetadataCollection->getOperation($attributes['operation_name'] ?? null);
        $exceptionToStatus = [$operation->getExceptionToStatus() ?: []];

        foreach ($resourceMetadataCollection as $resourceMetadata) {
            /* @var ApiResource $resourceMetadata */
            $exceptionToStatus[] = $resourceMetadata->getExceptionToStatus() ?: [];
        }

        return array_merge(...$exceptionToStatus);
    }

    private function getStatusCode(?HttpOperation $apiOperation, Request $request, ?HttpOperation $errorOperation, \Throwable $exception): int
    {
        $exceptionToStatus = array_merge(
            $this->exceptionToStatus,
            $apiOperation ? $apiOperation->getExceptionToStatus() ?? [] : $this->getOperationExceptionToStatus($request),
            $errorOperation ? $errorOperation->getExceptionToStatus() ?? [] : []
        );

        foreach ($exceptionToStatus as $class => $status) {
            if (is_a($exception::class, $class, true)) {
                return $status;
            }
        }

        if ($exception instanceof SymfonyHttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof RequestExceptionInterface) {
            return 400;
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($status = $errorOperation?->getStatus()) {
            return $status;
        }

        return 500;
    }

    private function getFormatOperation(?string $format): ?string
    {
        return match ($format) {
            'json' => '_api_errors_problem',
            'jsonproblem' => '_api_errors_problem',
            'jsonld' => '_api_errors_hydra',
            'jsonapi' => '_api_errors_jsonapi',
            'html' => '_api_errors_swagger_ui',
            default => '_api_errors_problem'
        };
    }

    /**
     * @param array<string, string|string[]> $errorFormats
     */
    private function getErrorFormat(Request $request, array $outputFormats = [], array $errorFormats = []): string
    {
        $accept = $request->headers->get('Accept');
        $contentType = $request->headers->get('Content-Type');

        if (!$accept && !$contentType) {
            // None found, defaults to the first error format
            return array_key_first($errorFormats);
        }

        if ($accept && $format = $this->getMimeTypeFormat($accept, $outputFormats)) {
            return $format;
        }

        // TODO: IMO this should find the first supported format and not give an arbitrary format
        // this means that if the accept is wrong we use jsonproblem, but if its another error we'll use jsonld at
        // features/security/validate_response_types.feature:20
        if ($accept && !$format) {
            return array_key_first($errorFormats);
        }

        if ($contentType && $format = $this->getMimeTypeFormat($contentType, $outputFormats)) {
            return $format;
        }

        foreach ($outputFormats as $format => $_) {
            if (isset($errorFormats[$format])) {
                return $format;
            }
        }

        return array_key_first($errorFormats);
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
     * Flattend mime types.
     *
     * @param array<string, string|string[]> $formats
     *
     * @return string[]
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
}
