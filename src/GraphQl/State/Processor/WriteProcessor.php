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

namespace ApiPlatform\GraphQl\State\Processor;

use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

/**
 * Calls the WriteProcessor
 */
final class WriteProcessor implements ProcessorInterface
{
    public function __construct(private readonly ProcessorInterface $inner, private readonly ProcessorInterface $callableProcessor)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (null === $data || !($operation->canWrite() ?? $operation instanceof Mutation)) {
            return $this->inner->process($data, $operation, $uriVariables, $context);
        }

        return $this->inner->process($this->callableProcessor->process($data, $operation, $uriVariables, $context), $operation, $uriVariables, $context);
    }
}
