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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7508;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'Issue7508Organization',
    operations: [
        new Get(
            uriTemplate: '/issue7508_organizations/{id}',
            provider: [self::class, 'itemProvider'],
        ),
    ],
)]
class Organization
{
    public function __construct(public readonly string $id, public readonly string $name = 'Test Organization')
    {
    }

    public static function itemProvider(Operation $operation, array $uriVariables = []): ?self
    {
        return new self($uriVariables['id']);
    }
}
