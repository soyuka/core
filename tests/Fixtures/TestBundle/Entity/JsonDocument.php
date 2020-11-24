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

class POPO
{
    public ?string $foo = null;
    public ?int $bar = null;
}

/**
 * This is a typical Doctrine ORM entity.
 *
 * @ApiResource
 * @ORM\Entity
 */
class JsonDocument
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * Can contain anything (array, objects, nested objects...).
     *
     * @ORM\Column(type="json_document", options={"jsonb": true})
     */
    public POPO $misc;
}
