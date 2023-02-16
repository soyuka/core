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

namespace ApiPlatform\State;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\ApiResource\ProblemError;
use ApiPlatform\Metadata\Operation;

final class DefaultProblemErrorProvider implements ProviderInterface
{
    public function __construct(private readonly IriConverterInterface $iriConverter)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ProblemError
    {
        $exception = $context['previous_data'];

        $problem = ProblemError::createFromException($exception, $uriVariables['status']);
        $identifiers = [
            'uri_variables' => ['status' => $problem->getStatus()],
        ];
        $problem->setType($this->iriConverter->getIriFromResource($problem, context: $identifiers));

        return $problem;
    }
}
