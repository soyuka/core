<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Dto;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operation;

#[GetCollection(
    uriTemplate: '/crawls/{crawlId}/crawl-urls/{id}',
    uriVariables: ['crawlId' => new Link(securityObjectName: 'object', security: 'is_granted("ROLE_ADMIN")')],
    provider: [self::class, 'provide'],
)]
class CrawlUrl {
    public function __construct(
        public string $id
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables) {
        $crawl = Crawl::provide($operation, ['id' => $uriVariables['crawlId']]);
        return new CrawlUrl($uriVariables['id']);
    }
}
