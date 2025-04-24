<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadata;
use Symfony\Component\JsonStreamer\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;

final class PropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private readonly PropertyMetadataLoaderInterface $loader,
        private readonly ResourceClassResolverInterface $resourceClassResolver,
    ) {
    }

    public function load(string $className, array $options = [], array $context = []): array
    {
        $properties = $this->loader->load($className, $options, $context);

        if (!$this->resourceClassResolver->isResourceClass($className)) {
            return $properties;
        }

        $properties['@id'] = new PropertyMetadata(
            'id', // virtual property
            Type::mixed(), // virtual property
            ['api_platform.jsonld.json_streamer.write.value_transformer.iri'],
        );

        $properties['@context'] = new PropertyMetadata(
            'id', // virual property
            Type::mixed(), // virtual property
            ['api_platform.jsonld.json_streamer.write.value_transformer.context'],
        );

        return $properties;
    }
}
