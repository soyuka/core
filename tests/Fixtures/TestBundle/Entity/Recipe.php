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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\FreeTextQueryFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrFilter;
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    types: ['https://schema.org/Recipe'],
    normalizationContext: ['hydra_prefix' => false],
    paginationItemsPerPage: 2,
    operations: [
        new GetCollection(
            parameters: [
                'q' => new QueryParameter(filter: new FreeTextQueryFilter(new OrFilter(new PartialSearchFilter()), properties: ['name', 'description'])),
            ]
        ),
        new Get(),
        new Post(),
        new Patch(),
    ]
)]
#[ORM\Entity]
class Recipe
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
    public string $name;

    #[ORM\Column(type: 'text')]
    public string $description;

    #[ORM\Column(nullable: true)]
    public ?string $cookTime = null;

    #[ORM\Column(nullable: true)]
    public ?string $prepTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
