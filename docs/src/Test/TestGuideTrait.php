<?php

/*
 * This file is part of the PHP Documentation Generator project
 *
 * (c) Antoine Bluchet <soyuka@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Playground\Test;

trait TestGuideTrait
{
    protected function setUp(): void
    {
        $kernel = static::createKernel();
        $kernel->executeMigrations();
        $kernel->loadFixtures();
    }
}

