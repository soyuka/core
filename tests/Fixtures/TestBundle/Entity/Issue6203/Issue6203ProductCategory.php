<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6203;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ApiResource(shortName: 'issue6203_product_category')]
class Issue6203ProductCategory
{
    public function __construct() {
        $this->products = new ArrayCollection();
    }

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    #[ORM\OneToMany(targetEntity: Issue6203Product::class, mappedBy: 'product')]
    public Collection $products;
}
