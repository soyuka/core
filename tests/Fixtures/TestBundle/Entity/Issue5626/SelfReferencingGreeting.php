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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5626;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5626\SelfReferencingGreetingDto;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Reproducer for issue 5626 - regression where modifying output to a DTO causes infinite loop in schema types.
 */
#[ApiResource(
    normalizationContext: ['groups' => ['simple']],
    operations: [
        new Get(
            output: SelfReferencingGreetingDto::class,
            normalizationContext: ['groups' => ['advanced']],
            provider: [self::class, 'provide']
        ),
        new Post(),
    ]
)]
#[ORM\Entity]
class SelfReferencingGreeting
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    #[Groups(['simple', 'advanced'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['simple', 'advanced'])]
    public string $name = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function provide(): SelfReferencingGreetingDto
    {
        $greeting = new self();
        $greeting->name = 'Test';

        return new SelfReferencingGreetingDto($greeting, 42);
    }
}
