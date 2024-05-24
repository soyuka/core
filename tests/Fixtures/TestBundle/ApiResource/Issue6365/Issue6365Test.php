<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6365;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;

#[ApiResource(provider: [self::class, 'provide'])]
final class Issue6365Test
{
    /**
     * @param Issue6365User[] $users
     */
    public function __construct(
        public int $id,
        public array $users,
    ) {
    }

    static public function provide(): PartialPaginatorInterface
    {
        return new class implements PartialPaginatorInterface, \IteratorAggregate
        {
            public function getIterator(): \Traversable
            {
                return new \ArrayIterator([
                    new Issue6365Test(1, [
                        new Issue6365User(1, 'test', 'test'),
                        new Issue6365User(1, 'test', 'test')
                    ]),
                ]);
            }

			public function getCurrentPage(): float {
				return 1.;
            }

            public function getItemsPerPage(): float
            {
                return 10.0;
            }

            public function count(): int
            {
                return 1;
            }

            public function getTotalItems(): float
            {
                return 1.;
            }
        };
    }
}
