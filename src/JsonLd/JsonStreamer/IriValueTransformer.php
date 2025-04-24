<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\JsonStreamer\ValueTransformer\ValueTransformerInterface;
use Symfony\Component\TypeInfo\Type;

final class IriValueTransformer implements ValueTransformerInterface
{
    public function __construct(
        private readonly IriConverterInterface $iriConverter,
    ) {
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        if ($options['operation'] instanceof CollectionOperationInterface) {
            return $this->iriConverter->getIriFromResource($options['operation']->getClass(), UrlGeneratorInterface::ABS_PATH, $options['operation']);
        }

        return $this->iriConverter->getIriFromResource($options['object'], UrlGeneratorInterface::ABS_PATH, $options['operation']);
    }

    public static function getStreamValueType(): Type
    {
        return Type::string();
    }
}
