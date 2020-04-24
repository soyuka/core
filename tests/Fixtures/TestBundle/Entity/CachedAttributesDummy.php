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

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy with cached attributes.
 *
 * @ApiResource(
 *     attributes={"cache_headers"={"max_age"=60, "shared_max_age"=120}, "order"="DESC"}
 * )
 * @ORM\Entity
 */
class CachedAttributesDummy
{
    /**
     * @ORM\Id
     * @ORM\Column
     */
    public $id;
}
