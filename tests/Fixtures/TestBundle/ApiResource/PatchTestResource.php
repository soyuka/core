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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Dto\PatchTestInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PatchTestEntity;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ApiResource(
    shortName: 'PatchTest',
    stateOptions: new Options(entityClass: PatchTestEntity::class),
    operations: [
        new Get(),
        new Post(),
        new Patch(
            input: PatchTestInput::class,
            uriTemplate: '/patch_test_resources/{id}',
            status: 200,
            deserialize: true
        ),
    ]
)]
#[Map(source: PatchTestEntity::class)]
final class PatchTestResource
{
    public ?int $id = null;
    public string $name;
    public ?string $description = null;
    public ?string $status = null;
}
