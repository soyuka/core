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

namespace ApiPlatform\Validator\Exception;

use ApiPlatform\Metadata\Exception\RuntimeException;

/**
 * Thrown when a validation error occurs.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
<<<<<<< Updated upstream
class ValidationException extends RuntimeException
=======
#[ErrorResource(
    uriTemplate: '/validation_errors/{id}',
    status: 422,
    openapi: false,
    uriVariables: ['id'],
    provider: 'api_platform.validator.state.error_provider',
    shortName: 'ConstraintViolationList',
    operations: [
        new ErrorOperation(
            name: '_api_validation_errors_problem',
            routeName: 'api_validation_errors',
            outputFormats: ['json' => ['application/problem+json']],
            normalizationContext: ['groups' => ['json'],
                'skip_null_values' => true,
                'rfc_7807_compliant_errors' => true,
            ]),
        new ErrorOperation(
            name: '_api_validation_errors_hydra',
            routeName: 'api_validation_errors',
            outputFormats: ['jsonld' => ['application/problem+json']],
            links: [new Link(rel: 'http://www.w3.org/ns/json-ld#error', href: 'http://www.w3.org/ns/hydra/error')],
            normalizationContext: [
                'groups' => ['jsonld'],
                'skip_null_values' => true,
                'rfc_7807_compliant_errors' => true,
            ]
        ),
        new ErrorOperation(
            name: '_api_validation_errors_jsonapi',
            routeName: 'api_validation_errors',
            outputFormats: ['jsonapi' => ['application/vnd.api+json']],
            normalizationContext: ['groups' => ['jsonapi'], 'skip_null_values' => true, 'rfc_7807_compliant_errors' => true]
        ),
    ],
    graphQlOperations: []
)]
class ValidationException extends RuntimeException implements ConstraintViolationListAwareExceptionInterface, \Stringable, ProblemExceptionInterface, HttpExceptionInterface, SymfonyHttpExceptionInterface
>>>>>>> Stashed changes
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, protected readonly ?string $errorTitle = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getErrorTitle(): ?string
    {
        return $this->errorTitle;
    }
}
