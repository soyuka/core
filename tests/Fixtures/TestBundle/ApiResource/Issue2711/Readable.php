<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue2711;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;

#[ApiResource(provider: [self::class, 'provide'])]
class Readable
{
    public function __construct(
        public int $id,
        public string $name,
        #[ApiProperty(readable: false, writable: false)] public string $secret
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return new self(1, 'test', 'secret');
    }
}
