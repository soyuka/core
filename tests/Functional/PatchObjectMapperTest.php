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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PatchTestResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PatchTestEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PatchObjectMapperTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            PatchTestResource::class,
        ];
    }

    public function testOmittedPropertiesAreNotModifiedOnPatch(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([PatchTestEntity::class]);
        $manager = $this->getManager();

        $entity = new PatchTestEntity();
        $entity->setName('Initial Name');
        $entity->setDescription('Initial Description');
        $entity->setStatus('Initial Status');
        $manager->persist($entity);
        $manager->flush();

        $id = $entity->getId();

        $client = self::createClient();
        $client->request(
            'PATCH',
            "/patch_test_resources/{$id}",
            [
                'json' => [
                    'name' => 'Updated Name',
                ],
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => "/patch_test_resources/{$id}",
            'id' => $id,
            'name' => 'Updated Name',
            'description' => 'Initial Description',
            'status' => 'Initial Status',
        ]);

        $updatedEntity = $manager->getRepository(PatchTestEntity::class)->find($id);
        self::assertNotNull($updatedEntity);
        self::assertSame('Updated Name', $updatedEntity->getName());
        self::assertSame('Initial Description', $updatedEntity->getDescription());
        self::assertSame('Initial Status', $updatedEntity->getStatus());
    }

    public function testCanExplicitlyNullifyPropertyOnPatch(): void
    {
        if (!$this->getContainer()->has('api_platform.object_mapper')) {
            $this->markTestSkipped('ObjectMapper not installed');
        }

        $this->recreateSchema([PatchTestEntity::class]);
        $manager = $this->getManager();

        $entity = new PatchTestEntity();
        $entity->setName('Initial Name');
        $entity->setDescription('Initial Description');
        $entity->setStatus('Initial Status');
        $manager->persist($entity);
        $manager->flush();

        $id = $entity->getId();

        $client = self::createClient();
        $client->request(
            'PATCH',
            "/patch_test_resources/{$id}",
            [
                'json' => [
                    'description' => null,
                ],
                'headers' => [
                    'Content-Type' => 'application/merge-patch+json',
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@id' => "/patch_test_resources/{$id}",
            'id' => $id,
            'name' => 'Initial Name',
            'description' => null,
            'status' => 'Initial Status',
        ]);

        $updatedEntity = $manager->getRepository(PatchTestEntity::class)->find($id);
        self::assertNotNull($updatedEntity);
        self::assertSame('Initial Name', $updatedEntity->getName());
        self::assertNull($updatedEntity->getDescription());
        self::assertSame('Initial Status', $updatedEntity->getStatus());
    }
}
