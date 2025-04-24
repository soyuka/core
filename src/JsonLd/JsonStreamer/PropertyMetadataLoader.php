<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
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

        if ((string) $context['original_type'] === Collection::class || ($this->resourceClassResolver->isResourceClass((string) $context['original_type']) && !isset($context['generated_classes'][Collection::class]))) {
            $properties['@context'] = new PropertyMetadata(
                'id', // virual property
                Type::string(), // virtual property
                // ['api_platform.jsonld.json_streamer.write.value_transformer.context'],
                staticValue: $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $options['operation']->getShortName()], $options['operation']->getUrlGenerationStrategy()),
            );
        }

        return $properties;
    }
}
