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

use ApiPlatform\Core\Metadata\ResourceCollection\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource;

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
            $resourceMetadata->input = $resourceMetadata->input ? $this->transformInputOutput($resourceMetadata->input) : null;
            $resourceMetadata->output = $resourceMetadata->output ? $this->transformInputOutput($resourceMetadata->output) : null;

            $resourceMetadata->operations = $this->getTransformedOperations($resourceMetadata->operations, $resourceMetadata);

            if (null !== $graphQlAttributes = $resourceMetadata->graphql) {
                $resourceMetadata->graphql = $this->getTransformedOperations($resourceMetadata->graphql, $resourceMetadata);
            }

            $resourceMetadataCollection[$key] = $resourceMetadata;
        }

        return new ResourceMetadataCollection($resourceMetadataCollection);
    }

    private function getTransformedOperations(array $operations, Resource $resourceMetadata): array
    {
        foreach ($operations as $key => &$operation) {
            if (!\is_array($operation)) {
                continue;
            }

            $operation->input = $operation->input ? $this->transformInputOutput($operation->input) : $resourceMetadata->input;
            $operation->output = $operation->output ? $this->transformInputOutput($operation->output) : $resourceMetadata->output;

            if (
                $operation->input
                && \array_key_exists('class', $operation->input)
                && null === $operation->input['class']
            ) {
                $operation->deserialize = $operation->deserialize ?? false;
                $operation->validate = $operation->validate ?? false;
            }

            if (
                $operation->output
                && \array_key_exists('class', $operation->output)
                && null === $operation->output['class']
            ) {
                $operation->status = $operation->status ?? 204;
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
