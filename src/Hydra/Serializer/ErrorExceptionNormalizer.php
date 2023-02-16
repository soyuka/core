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

namespace ApiPlatform\Hydra\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ErrorExceptionNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    use NormalizerAwareTrait;

    public const FORMAT = 'jsonld';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly IriConverterInterface $iriConverter,
        private readonly bool $debug = false
    ) {
    }

    public function normalize(mixed $object, string $format = null, array $context = []): float|array|\ArrayObject|bool|int|string|null
    {
        $data = $this->normalizer->normalize($object, context: [AbstractNormalizer::IGNORED_ATTRIBUTES => ['trace', 'traceAsString', 'message', 'file', 'line', 'code', 'previous', 'identifiers']]);
        $data['@context'] = $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'Error']);
        $data['@type'] = 'hydra:Error';
        $data['@id'] = $this->iriConverter->getIriFromResource($object);

        if ($this->debug && $trace = $object->getTrace()) {
            $data['trace'] = $trace;
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return self::FORMAT === $format && $data instanceof \Exception;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}
