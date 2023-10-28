<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5926;

use Traversable;

class ContentItemCollection implements \IteratorAggregate
{
    private array $items;
    public function __construct(ContentItemInterface ...$items)
    {
        $this->items = $items;
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }
}
