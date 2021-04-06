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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceMetadataCollection;

/**
 * Transforms the given input/output metadata to a normalized one.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class InputOutputResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            $extraProperties = $resourceMetadata->getExtraProperties() ?: [];
            $extraProperties['input'] = isset($extraProperties['input']) ? $this->transformInputOutput($extraProperties['input']) : null;
            $extraProperties['output'] = isset($extraProperties['output']) ? $this->transformInputOutput($extraProperties['output']) : null;

            $resourceMetadata = $resourceMetadata->withOperations($this->getTransformedOperations($resourceMetadata->getOperations(), $extraProperties));

            if (null !== $graphQlAttributes = $resourceMetadata->getGraphql()) {
                $resourceMetadata = $resourceMetadata->withGraphql($this->getTransformedOperations($graphQlAttributes, $extraProperties));
            }

            $resourceMetadataCollection[$key] = $resourceMetadata->withExtraProperties($extraProperties);
        }

        return new ResourceMetadataCollection($resourceMetadataCollection);
    }

    private function getTransformedOperations(array $operations, array $resourceAttributes): array
    {
        foreach ($operations as $key => &$operation) {
            if (!\is_array($operation)) {
                continue;
            }

            $operation['input'] = isset($operation['input']) ? $this->transformInputOutput($operation['input']) : $resourceAttributes['input'];
            $operation['output'] = isset($operation['output']) ? $this->transformInputOutput($operation['output']) : $resourceAttributes['output'];

            if (
                isset($operation['input'])
                && \array_key_exists('class', $operation['input'])
                && null === $operation['input']['class']
            ) {
                $operation['deserialize'] ?? $operation['deserialize'] = false;
                $operation['validate'] ?? $operation['validate'] = false;
            }

            if (
                isset($operation['output'])
                && \array_key_exists('class', $operation['output'])
                && null === $operation['output']['class']
            ) {
                $operation['status'] ?? $operation['status'] = 204;
            }
        }

        return $operations;
    }

    private function transformInputOutput($attribute): ?array
    {
        if (null === $attribute) {
            return null;
        }

        if (false === $attribute) {
            return ['class' => null];
        }

        if (\is_string($attribute)) {
            $attribute = ['class' => $attribute];
        }

        if (!isset($attribute['name']) && isset($attribute['class'])) {
            $attribute['name'] = (new \ReflectionClass($attribute['class']))->getShortName();
        }

        return $attribute;
    }
}
