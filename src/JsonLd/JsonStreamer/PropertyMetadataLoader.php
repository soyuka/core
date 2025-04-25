<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use ApiPlatform\Hydra\Collection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\TypeHelper;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadata;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;

final class PropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private readonly PropertyMetadataLoaderInterface $loader,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function load(string $className, array $options = [], array $context = []): array
    {
        $properties = $this->loader->load($className, $options, $context);

        if ($className !== Collection::class && !$this->resourceClassResolver->isResourceClass($className)) {
            return $properties;
        }

        $properties['@id'] = new PropertyMetadata(
            'id', // virtual property
            Type::mixed(), // virtual property
            ['api_platform.jsonld.json_streamer.write.value_transformer.iri'],
        );

        $originalClassName = TypeHelper::getClassName($context['original_type']);

        if ($originalClassName === Collection::class || ($this->resourceClassResolver->isResourceClass($originalClassName) && !isset($context['generated_classes'][Collection::class]))) {
            $properties['@context'] = new PropertyMetadata(
                'id', // virual property
                Type::string(), // virtual property
                staticValue: $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $options['operation']->getShortName()], $options['operation']->getUrlGenerationStrategy()),
            );
        }

        return $properties;
    }
}
