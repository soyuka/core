<?php

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

final class Patch extends ApiTestCase
{
    /**
     * The input DTO denormalizes an existing Doctrine entity.
     */
    public function testIssue6465(): void
    {
        $response = self::createClient()->request('PATCH', '/patch_required_stuff', [
            'json' => ['a' => 'not-required'],
            'headers' => ['content-type' => 'application/merge-patch+json']
        ]);

        $data = $response->toArray(false);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('b: This value should not be null.', $data['detail']);
    }
}
