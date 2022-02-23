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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection;

use ApiPlatform\Symfony\Bundle\DependencyInjection\ApiPlatformExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ApiPlatformExtensionTest extends TestCase
{
    public const DEFAULT_CONFIG = ['api_platform' => [
        'title' => 'title',
        'metadata_backward_compatibility_layer' => false,
        'description' => 'description',
        'version' => 'version',
        'formats' => [
            'jsonld' => ['mime_types' => ['application/ld+json']],
            'jsonhal' => ['mime_types' => ['application/hal+json']],
        ],
        'http_cache' => ['invalidation' => [
            'enabled' => true,
            'varnish_urls' => ['test'],
            'xkey' => [
                'enabled' => false,
                'glue' => ' ',
            ],
            'http_tags' => [
                'enabled' => true,
            ],
            'request_options' => [
                'allow_redirects' => [
                    'max' => 5,
                    'protocols' => ['http', 'https'],
                    'stric' => false,
                    'referer' => false,
                    'track_redirects' => false,
                ],
                'http_errors' => true,
                'decode_content' => false,
                'verify' => false,
                'cookies' => true,
                'headers' => [
                    'User-Agent' => 'none',
                ],
            ],
        ]],
        'doctrine_mongodb_odm' => [
            'enabled' => false,
        ],
        'defaults' => [
            'attributes' => [],
        ],
    ]];

    /**
     * @return ParameterBag
     */
    private function getPartialContainerParameter($configuration = null)
    {
        // modèle : getPartialContainerBuilderProphecy // TODO à virer
        $parameterBag = new ParameterBag([
            'kernel.bundles_metadata' => [
                'TestBundle' => [
                    'parent' => null,
                    'path' => realpath(__DIR__.'/../../../Fixtures/TestBundle'),
                    'namespace' => TestBundle::class,
                ],
            ],
        // TODO il y a plein de truc entre
            'kernel.project_dir' => __DIR__ . '/../../../Fixtures/app',
            'kernel.debug' => false,
        ]);


        return $parameterBag;
    }

    /**
     * @return ParameterBag
     */
    private function getBaseContainerParameterWithoutDefaultMetadataLoading(
        array $doctrineIntegrationsToLoad = ['orm'],
        $configuration = null
    ) {
        // modèle : getBaseContainerBuilderProphecyWithoutDefaultMetadataLoading // TODO à virer
        $hasSwagger = null === $configuration || true === $configuration['api_platform']['enable_swagger'];
        $hasHydra = null === $configuration || isset($configuration['api_platform']['formats']['jsonld']);
        $hasHal = null === $configuration || isset($configuration['api_platform']['formats']['jsonhal']);

        $parameterBag = $this->getPartialContainerParameter($configuration);

        $parameterBag->set('kernel.bundles', [
            'DoctrineBundle' => DoctrineBundle::class,
        ]);

        // TODO cf modèle


        return $parameterBag;
    }


    /**
     * @return ParameterBag
     */
    private function getBaseContainerParameterBag(
        array $doctrineIntegrationsToLoad = ['orm'],
        $configuration = null
    ) {
        // modèle : getBaseContainerBuilderProphecy // TODO à virer
        $parameterBag = $this->getBaseContainerParameterWithoutDefaultMetadataLoading($doctrineIntegrationsToLoad, $configuration);

        // TODO cf modèle

        return $parameterBag;
    }

    public function testEa(): void
    {
        $contairerParameterBag = $this->getBaseContainerParameterBag();

        $container = new ContainerBuilder($contairerParameterBag);
        (new ApiPlatformExtension())->load(self::DEFAULT_CONFIG, $container);

        $this->assertTrue($container->hasDefinition('api_platform.action.get_item'));
    }
}