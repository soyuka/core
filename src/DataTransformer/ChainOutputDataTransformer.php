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

namespace ApiPlatform\Core\DataTransformer;

/**
 * Transforms an Output to a Resource object.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ChainOutputDataTransformer implements DataTransformerInterface
{
    private $transformers;

    public function __construct(iterable $transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($object, array $context = [])
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supportsTransformation($object, $context)) {
                return $transformer->transform($object, $context);
            }
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($object, array $context = []): bool
    {
        foreach ($this->transformers as $transformer) {
            if ($transformer->supportsTransformation($object, $context)) {
                return true;
            }
        }

        return false;
    }
}
