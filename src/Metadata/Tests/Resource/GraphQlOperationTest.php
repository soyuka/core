<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Metadata\Tests\Resource;

use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\State\OptionsInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GraphQlOperationTest extends TestCase
{
    #[DataProvider('getGraphQlOperations')]
    public function testHasStateOptions(string $operationClass): void
    {
        $opts = new class implements OptionsInterface {};
        $o = new $operationClass(stateOptions: $opts);
        $this->assertSame($opts, $o->getStateOptions());
    }

    /**
     * @return iterable<array{class-string<Operation>}>
     */
    public static function getGraphQlOperations(): iterable
    {
        yield [QueryCollection::class];
        yield [Query::class];
        yield [DeleteMutation::class];
        yield [Mutation::class];
        yield [Subscription::class];
    }
}
