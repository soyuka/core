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

namespace ApiPlatform\JsonLd;

use Symfony\Component\Marshaller\Attribute\Name;

trait Item 
{
    #[Name('@type')]
    /** @var array<int, string>|string */
    public array|string $_type;

    #[Name('@id')]
    public string $_id;

    #[Name('@context')]
    public string $_context;
}
