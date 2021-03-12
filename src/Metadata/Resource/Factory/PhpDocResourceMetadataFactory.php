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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use phpDocumentor\Reflection\Types\ContextFactory;

/**
 * Extracts descriptions from PHPDoc.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PhpDocResourceMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    use ResourceMetadataFactoryCompatibilityTrait;

    private $decorated;
    private $docBlockFactory;
    private $contextFactory;

    public function __construct(ResourceMetadataFactoryInterface $decorated, DocBlockFactoryInterface $docBlockFactory = null)
    {
        $this->decorated = $decorated;
        $this->docBlockFactory = $docBlockFactory ?: DocBlockFactory::createInstance();
        $this->contextFactory = new ContextFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): array
    {
        $resourceMetadataCollection = $this->getMetadataAsArray($this->decorated->create($resourceClass));

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            if (null !== $resourceMetadata->getDescription()) {
                continue;
            }

            $reflectionClass = new \ReflectionClass($resourceClass);

            try {
                $docBlock = $this->docBlockFactory->create($reflectionClass, $this->contextFactory->createFromReflector($reflectionClass));
                $resourceMetadataCollection[$key] = $resourceMetadata->withDescription($docBlock->getSummary());
            } catch (\InvalidArgumentException $e) {
                // Ignore empty DocBlocks
            }
        }

        return $resourceMetadataCollection;
    }
}
