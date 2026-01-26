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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Dto;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PatchTestEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: PatchTestEntity::class)]
final class PatchTestInput
{
    public ?string $name;
    public ?string $description;
    public ?string $status;
}
