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

namespace ApiPlatform\Core\OpenApi\Serializer;

use ApiPlatform\Core\OpenApi\Model\Paths;
use ApiPlatform\Core\OpenApi\OpenApi;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Generates an OpenAPI v3 specification.
 */
final class OpenApiNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = 'json';
    private const EXTENSION_PROPERTIES_KEY = 'extensionProperties';

    private $propertyAccessor;
    private $propertyInfo;

    public function __construct(PropertyAccessorInterface $propertyAccessor, PropertyInfoExtractorInterface $propertyInfo)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->propertyInfo = $propertyInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        return $this->objectToArray($object);
    }

    private function objectToArray($object)
    {
        if (!\is_object($object)) {
            return $object;
        }

        if ($object instanceof Paths) {
            $paths = $object->getPaths();
            ksort($paths);

            return array_map([$this, 'objectToArray'], $paths);
        }

        if ($object instanceof \ArrayObject) {
            if (0 === $object->count()) {
                return $object;
            }

            return array_map([$this, 'objectToArray'], $object->getArrayCopy());
        }

        $array = [];
        foreach ($this->propertyInfo->getProperties(\get_class($object)) as $property) {
            $value = $this->propertyAccessor->getValue($object, $property);

            if (\is_object($value)) {
                $array[$property] = $this->objectToArray($value);
                continue;
            }

            if (self::EXTENSION_PROPERTIES_KEY === $property) {
                foreach ($value as $extensionPropertyKey => $extensionPropertyValue) {
                    $array[$extensionPropertyKey] = $extensionPropertyValue;
                }
                continue;
            }

            if (is_iterable($value)) {
                $array[$property] = [];

                foreach ($value as $key => $v) {
                    $array[$property][$key] = $this->objectToArray($v);
                }
                continue;
            }

            if (null === $value) {
                continue;
            }

            $array[$property] = $value;
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return self::FORMAT === $format && $data instanceof OpenApi;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
