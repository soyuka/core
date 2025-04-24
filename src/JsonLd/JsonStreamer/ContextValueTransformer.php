<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueTransformerInterface;
use Symfony\Component\TypeInfo\Type;

final class ContextValueTransformer implements ValueTransformerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $options['operation']->getShortName()], $options['operation']->getUrlGenerationStrategy());
    }

    public static function getStreamValueType(): Type
    {
        return Type::string();
    }
}
