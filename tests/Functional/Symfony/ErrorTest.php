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

namespace ApiPlatform\Tests\Functional\Symfony;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6729\ControllerError;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ErrorTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ControllerError::class];
    }

    public function testWithGroupFilter(): void
    {
        $container = $this->getContainer();
        if (!$container->getParameter('api_platform.use_symfony_listeners')) {
            $this->markTestSkipped('This is a listener-only test.');
        }

        $response = self::createClient()->request('POST', '/controller_error', [
            'headers' => [
                'accept' => 'application/json',
                'content-type' => 'application/ld+json',
            ],
            'body' => 'invalid input{',
        ]);

        $this->assertJsonContains(['detail' => 'Syntax error', 'status' => 400]);
    }
}
