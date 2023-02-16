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

namespace ApiPlatform\Exception;

interface ProblemExceptionInterface
{
    public function getType(): string;

    public function getTitle(): string;

    public function getStatus(): int;

    public function getDetail(): string;

    public function getInstance(): string;
}
