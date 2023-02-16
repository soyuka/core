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

use ApiPlatform\ApiResource\Error;
use ApiPlatform\Metadata\Operation;

class DefaultErrorProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Error
    {
        $exception = $context['previous_data'];

        return Error::createFromException($exception, $uriVariables['statusCode']);
    }
}
