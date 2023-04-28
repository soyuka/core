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

use ApiPlatform\Action\ExceptionAction;
use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\ApiResource\Error;
use ApiPlatform\ApiResource\ProblemError;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\ErrorFormatGuesser;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\ErrorListener as SymfonyErrorListener;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null,
        private readonly array $errorFormats = [],
        private readonly array $exceptionToStatus = [],
        private readonly ?IdentifiersExtractorInterface $identifiersExtractor = null
    ) {
        parent::__construct($controller, $logger, $debug, $exceptionsMapping);
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    protected function duplicateRequest(\Throwable $exception, Request $request): Request
    {
        $dup = parent::duplicateRequest($exception, $request);

        $apiOperation = $this->initializeOperation($request);

        if (!($apiOperation?->getExtraProperties()['hydra_errors'] ?? null)) {
            $dup->attributes->set('_controller', 'api_platform.action.exception');
            if ($request->attributes->has('_api_operation')) {
                $dup->attributes->set('_api_operation', $request->attributes->get('_api_operation'));
            }

            return $dup;
        }

        // 1) chercher l'operation sur $exception::class
        $errorOperation = $this->resourceMetadataCollectionFactory->create($exception::class)->getOperation();

        if (!$errorOperation) {
            $resourceClass = $this->getResourceClass($exception, $request);
            $errorOperation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation();
            $errorOperation = $errorOperation->withStatusCode($this->getStatusCode());
            // $identifiers = match (true) {
            //     $resourceClass === $exception::class => $this->identifiersExtractor->getIdentifiersFromItem($exception, $operation),
            //         // problemecverptioninterface
            //     ProblemError::class === $resourceClass => $this->identifiersExtractor->getIdentifiersFromItem(ProblemError::createFromException($exception, $statusCode), $operation),
            //     default => $this->identifiersExtractor->getIdentifiersFromItem(Error::createFromException($exception, $statusCode), $operation),
            // };
            $data = ($resourceClass)::createFromException();
            // $this->identifiersExtractor->getIdentifiersFromItem(Error::createFromException($exception, $statusCode), $operation);
        }

        $dup->attributes->set('_api_resource_class', $resourceClass);
        $dup->attributes->set('_api_operation', $operation);
        $dup->attributes->set('_api_operation_name', $operation?->getName());
        $dup->attributes->remove('exception');
        $dup->attributes->set('data', $exception);
        // $dup->attributes->set('_original_exception', $exception);

        // $statusCode = $this->getStatusCode($apiOperation, $request, $operation, $exception);

        // $dup->attributes->set('_exception_status', $statusCode);

        // $identifiers = match (true) {
        //     $resourceClass === $exception::class => $this->identifiersExtractor->getIdentifiersFromItem($exception, $operation),
        //         // problemecverptioninterface
        //     ProblemError::class === $resourceClass => $this->identifiersExtractor->getIdentifiersFromItem(ProblemError::createFromException($exception, $statusCode), $operation),
        //     default => $this->identifiersExtractor->getIdentifiersFromItem(Error::createFromException($exception, $statusCode), $operation),
        // };

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

    private function getResourceClass(\Throwable $exception, Request $request): string
    {
        // $reflectionClass = new \ReflectionClass($exception);
        //
        // foreach ($reflectionClass->getAttributes() as $attribute) {
        //     if (is_a($attribute->getName(), ErrorResource::class, true)) {
        //         return $exception::class;
        //     }
        // }
        //
        $format = ErrorFormatGuesser::guessErrorFormat($request, $this->errorFormats);

        return match ($format['key']) {
            'jsonproblem' => ProblemError::class,
            default => Error::class,
        };
    }

    private function getStatusCode(?HttpOperation $apiOperation, Request $request, ?HttpOperation $operation, \Throwable $exception): int
    {
        if ($status = $operation?->getStatus()) {
            return $status;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof RequestExceptionInterface) {
            return Response::HTTP_BAD_REQUEST;
        }

        $exceptionToStatus = array_merge(
            $this->exceptionToStatus,
            $apiOperation ? $apiOperation->getExceptionToStatus() ?? [] : $this->getOperationExceptionToStatus($request),
            $operation ? $operation->getExceptionToStatus() ?? [] : []
        );

        foreach ($exceptionToStatus as $class => $status) {
            if (is_a($exception::class, $class, true)) {
                $statusCode = $status;

                break;
            }
        }

        $statusCode ??= 500;

        return $statusCode;
    }
}
