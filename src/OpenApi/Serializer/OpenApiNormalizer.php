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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
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

    public function __construct($propertyAccessor, PropertyInfoExtractorInterface $propertyInfo = null)
    {
        if (!$propertyAccessor instanceof PropertyAccessorInterface) {
            @trigger_error('Using a Normalizer is deprecated since 2.6.2 and will not be possible anymore in 3.0', \E_USER_DEPRECATED);
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        $this->propertyAccessor = $propertyAccessor;

        if (null === $propertyInfo) {
            @trigger_error('Not using PropertyInfo is deprecated since 2.6.2 and will not be possible anymore in 3.0', \E_USER_DEPRECATED);
            $reflectionExtractor = new ReflectionExtractor();
            $propertyInfo = new PropertyInfoExtractor(
                [$reflectionExtractor],
                [$reflectionExtractor],
                [],
                [$reflectionExtractor],
                [$reflectionExtractor]
            );
        }

        $this->propertyInfo = $propertyInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        $array = $this->recursiveTransform($object);
        if (isset($array['components']['schemas'])) {
            ksort($array['components']['schemas']);
        }

        return $array;
    }

    /**
     * Transforms the OpenApi object recursively
     * Nulls are removed, empty object must be kept, paths are sorted.
     */
    private function recursiveTransform($object)
    {
        if (!\is_object($object)) {
            return $object;
        }

        if ($object instanceof Paths) {
            $paths = $object->getPaths();
            ksort($paths);

            return array_map([$this, 'recursiveTransform'], $paths);
        }

        if ($object instanceof \ArrayObject) {
            if (0 === $object->count()) {
                return $object;
            }

            return array_map([$this, 'recursiveTransform'], $object->getArrayCopy());
        }

        $array = [];
        foreach ($this->propertyInfo->getProperties(\get_class($object)) as $property) {
            $value = $this->propertyAccessor->getValue($object, $property);

            if (\is_object($value)) {
                $array[$property] = $this->recursiveTransform($value);
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
                    $array[$property][$key] = $this->recursiveTransform($v);
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
