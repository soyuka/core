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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Attributes\Resource;
use ApiPlatform\Core\Attributes\Get;
use ApiPlatform\Core\Attributes\Put;
use ApiPlatform\Core\Attributes\Delete;
use ApiPlatform\Core\Attributes\Post;

#[Resource("/attribute_resources/{id}")]
#[Get]
#[Put]
#[Delete]
final class AttributeResource
{
    #[ApiProperty(identifier: true)]
    private int $id;

    public string $name;

    public function getId()
    {
        return $this->id;
    }
}

#[Resource("/attribute_resources")]
#[Get]
#[Post]
final class AttributeResources extends \ArrayObject
{
}
