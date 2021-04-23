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

namespace ApiPlatform\Metadata;

class Operation
{
    use AttributeTrait;
    public string $method = 'GET';
    public array $defaults = [];
    public array $requirements = [];
    public array $options = [];
    public string $host = '';
    public array $schemes = [];
    public string $condition = '';
    public string $controller = 'api_platform.action.placeholder';
}
