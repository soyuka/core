<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5926;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    operations: [
        new Get(
            provider: [TestIssue5926::class, 'provide']
        )
    ]
)]
class TestIssue5926
{
    public function __construct(
        private readonly string $id,
        private readonly ?ContentItemCollection $content,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContent(): ?ContentItemCollection
    {
        return $this->content;
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $media = new Media('1', 'My media');
        $contentItem1 = new MediaContentItem($media);
        $media = new Media('2', 'My media 2');
        $contentItem2 = new MediaContentItem($media);

        $collection = new ContentItemCollection($contentItem1, $contentItem2);

        $post = new self('1', $collection);

        return $post;
    }

}
