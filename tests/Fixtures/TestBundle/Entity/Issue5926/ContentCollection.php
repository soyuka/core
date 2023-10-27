<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5926;

class ContentCollection implements \IteratorAggregate
{
	private array $items;
	public function __construct(ContentItemInterface ...$items)
	{
		$this->items = $items;
	}
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->items);
	}
}
