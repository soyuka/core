<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5926;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get(uriTemplate: 'issue5926', provider: [HasContent::class, 'provide'])]
class HasContent
{
    public int $id;
    public ContentCollection $content;

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $y = new Media();
        $y->id = 2;

        $m = new MediaContentItem();
        $m->type = 'audio';
        $m->value = $y;

        $s = new self();
        $s->id = 1;
        $s->content = new ContentCollection($m);
        return $s;
    }
}
