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

use ApiPlatform\Metadata\Operation;

/**
 * Process data: send an email, persist to storage, add to queue etc.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface ProcessorInterface
{
    /**
     * Handle the state.
     *
     * @param array<string, mixed>                                         $uriVariables
     * @param array<string, mixed>&array{request?: ServerRequestInterface} $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []);
}
