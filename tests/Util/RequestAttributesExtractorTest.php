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

namespace ApiPlatform\Core\Tests\Util;

use ApiPlatform\Core\Util\RequestAttributesExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RequestAttributesExtractorTest extends TestCase
{
    public function testExtractCollectionAttributes()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'collection_operation_name' => 'post',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testExtractItemAttributes()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testExtractReceive()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_receive' => '0']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => false,
                'respond' => true,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_receive' => '1']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testExtractRespond()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_respond' => '0']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => false,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_respond' => '1']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testExtractPersist()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_persist' => '0']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => false,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_persist' => '1']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );

        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testResourceClassNotSet()
    {
        $this->assertEmpty(RequestAttributesExtractor::extractAttributes(new Request([], [], ['_api_item_operation_name' => 'get'])));
    }

    public function testOperationNotSet()
    {
        $this->assertEmpty(RequestAttributesExtractor::extractAttributes(new Request([], [], ['_api_resource_class' => 'Foo'])));
    }

    public function testExtractPreviousDataAttributes()
    {
        $object = new \stdClass();
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', 'previous_data' => $object]);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'previous_data' => $object,
                'identified_by' => null,
                'has_composite_identifier' => false,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }

    public function testExtractIdentifiedBy()
    {
        $request = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get', '_api_identified_by' => ['test'], '_api_has_composite_identifier' => true]);

        $this->assertEquals(
            [
                'resource_class' => 'Foo',
                'item_operation_name' => 'get',
                'receive' => true,
                'respond' => true,
                'persist' => true,
                'identified_by' => ['test'],
                'has_composite_identifier' => true,
            ],
            RequestAttributesExtractor::extractAttributes($request)
        );
    }
}
