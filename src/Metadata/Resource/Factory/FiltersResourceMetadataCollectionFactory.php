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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Util\AnnotationFilterExtractorTrait;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Doctrine\Common\Annotations\Reader;

/**
 * Creates a resource metadata from {@see Resource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class FiltersResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use AnnotationFilterExtractorTrait;

    private $reader;
    private $decorated;

    public function __construct(?Reader $reader = null, ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->reader = $reader;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection();

        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
        }

        $filters = array_keys($this->readFilterAnnotations($reflectionClass, $this->reader));

        if (!$filters) {
            return $resourceMetadataCollection;
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = iterator_to_array($resource->getOperations());

            foreach ($resource->getOperations() as $operationName => $operation) {
                $operations[$operationName] = $operation->withFilters($filters);
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }
}
