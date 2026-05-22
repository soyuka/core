<?php

declare(strict_types=1);

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Config\Repository;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class DocsWithCredentialsTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    protected function defineEnvironment($app): void
    {
        tap($app['config'], static function (Repository $config): void {
            $config->set('api-platform.swagger_ui.with_credentials', true);
        });
    }

    public function testSwaggerDataContainsWithCredentialsTrueWhenEnabled(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = (string) $res->getContent();
        $this->assertStringContainsString('"withCredentials":true', $content);
    }
}
