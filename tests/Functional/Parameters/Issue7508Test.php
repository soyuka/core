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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7508\Organization;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7508\Staff;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class Issue7508Test extends ApiTestCase
{
    use SetupClassResourcesTrait;
    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Organization::class, Staff::class];
    }

    public function testHeaderParameterWithIriConverterParameterProvider(): void
    {
        $organizationId = 'f2ba46cd-8009-4c7b-8cc4-a709befaa958';
        $organizationIri = '/issue7508_organizations/'.$organizationId;

        $response = self::createClient()->request('GET', '/issue7508_staffs', [
            'headers' => [
                'organization' => $organizationIri,
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();

        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(2, $data['hydra:member']);
        $this->assertEquals('John Doe', $data['hydra:member'][0]['name']);
        $this->assertEquals('Jane Smith', $data['hydra:member'][1]['name']);
    }
}
