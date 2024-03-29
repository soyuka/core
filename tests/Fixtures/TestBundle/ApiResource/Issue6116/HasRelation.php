<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6116;

use ApiPlatform\Metadata\Post;

#[Post(shortName: 'issue6116HasRelation', uriTemplate: 'issue6116')]
class HasRelation
{
    public string $id;
    public ?Relation $relation = null;
}
