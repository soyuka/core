<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6116;

use ApiPlatform\Metadata\Get;

#[Get(shortName: 'issue6116Relation', uriTemplate: 'issue6116_relations/{id}', provider: [Relation::class, 'provide'])]
class Relation
{
    public string $id;
    public string $name;

    static public function provide(): null {
        return null;
    }
}
