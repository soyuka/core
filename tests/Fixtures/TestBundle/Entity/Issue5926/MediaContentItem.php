<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5926;

class MediaContentItem implements ContentItemInterface
{
    public function __construct(
        private readonly Media $media,
    )
    {
    }

    public function getMedia(): Media
    {
        return $this->media;
    }
}
