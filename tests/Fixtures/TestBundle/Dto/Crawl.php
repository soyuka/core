<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto;

use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operation;

#[NotExposed('/crawls/{id}', uriVariables: ['id'], provider: [self::class, 'provide'])]
class Crawl
{
    public string $id;

    public static function provide(Operation $operation, array $uriVariables) {
        return new Crawl($uriVariables['id']);
    }
}
