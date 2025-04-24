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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6810\JsonLdContextOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465\Bar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465\Foo;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

class JsonLdTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Foo::class, Bar::class, JsonLdContextOutput::class];
    }

    /**
     * The input DTO denormalizes an existing Doctrine entity.
     */
    public function testIssue6465(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('POST', '/foo/1/validate', [
            'json' => ['bar' => '/bar6465s/2'],
        ]);

        ob_get_clean();

        $res = json_decode($buffer, true);
        dump($res);
        $this->assertEquals('Bar two', $res['title']);
    }

    public function testContextWithOutput(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/json_ld_context_output',
        );
        $res = $response->toArray();
        $this->assertEquals($res['@context'], [
            '@vocab' => 'http://localhost/docs.jsonld#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'foo' => 'Output/foo',
        ]);
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $classes = [];
        foreach ([Foo::class, Bar::class] as $entityClass) {
            $classes[] = $manager->getClassMetadata($entityClass);
        }

        try {
            $schemaTool = new SchemaTool($manager);
            @$schemaTool->createSchema($classes);
        } catch (\Exception $e) {
            return;
        }

        $foo = new Foo();
        $foo->title = 'Foo';
        $manager->persist($foo);
        $bar = new Bar();
        $bar->title = 'Bar one';
        $manager->persist($bar);
        $bar2 = new Bar();
        $bar2->title = 'Bar two';
        $manager->persist($bar2);
        $manager->flush();
    }

    protected function tearDown(): void
    {
        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $classes = [];
        foreach ([Foo::class, Bar::class] as $entityClass) {
            $classes[] = $manager->getClassMetadata($entityClass);
        }

        $schemaTool = new SchemaTool($manager);
        @$schemaTool->dropSchema($classes);
        parent::tearDown();
    }

    /**
     * The input DTO denormalizes an existing Doctrine entity.
     */
    public function testJsonStreamer(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $buffer = '';
        ob_start(function (string $chunk) use (&$buffer): void {
            $buffer .= $chunk;
        });

        self::createClient()->request('GET', '/foo6465s/1', ['headers' => ['accept' => 'application/ld+json']]);

        ob_get_clean();

        $res = json_decode($buffer, true);
        dump($res);
        $this->assertEquals('Bar two', $res['title']);
    }

}
