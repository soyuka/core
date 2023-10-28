<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5926;

use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
class Media
{
    public function __construct(
        private readonly string $id,
        private readonly string $title,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
