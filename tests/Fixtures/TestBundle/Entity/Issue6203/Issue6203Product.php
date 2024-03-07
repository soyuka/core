<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6203;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;

#[ApiFilter(ExistsFilter::class, properties: ['productCategory'])]
#[ApiResource(shortName: 'issue6203_product')]
#[ORM\Entity]
class Issue6203Product
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    #[ORM\ManyToOne(inversedBy: 'products', targetEntity: Issue6203ProductCategory::class)]
    #[ORM\JoinColumn(name: 'product_category_id', referencedColumnName: 'id')]
    public ?Issue6203ProductCategory $productCategory = null;
}
