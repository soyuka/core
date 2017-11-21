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

namespace ApiPlatform\Core\Hydra\Serializer;

use ApiPlatform\Core\Documentation\Documentation;
// use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Creates a machine readable Hydra API documentation.
 *
 * @author me
 */
final class CsvNormalizer implements NormalizerInterface
{
    const FORMAT = 'jsonld';

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $data = [];

        foreach ($object->getResourceNameCollection() as $resourceClass) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $shortName = $resourceMetadata->getShortName();
            // $prefixedShortName = $resourceMetadata->getIri() ?? "#$shortName";

            //$this->populateEntrypointProperties($resourceClass, $resourceMetadata, $shortName, $prefixedShortName, $entrypointProperties);
            // $classes[] = $this->getClass($resourceClass, $resourceMetadata, $shortName, $prefixedShortName);
            $properties = [];
            foreach ($this->propertyNameCollectionFactory->create($resourceClass, $this->getPropertyNameCollectionFactoryContext($resourceMetadata)) as $propertyName) {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
                if (true === $propertyMetadata->isIdentifier() && false === $propertyMetadata->isWritable()) {
                    continue;
                }

                $properties[] = $this->getProperty($propertyMetadata, $propertyName, $prefixedShortName, $shortName);
            }

            var_dump();
            die();
        }

    }

    /**
     * Gets the context for the property name factory.
     *
     * @param ResourceMetadata $resourceMetadata
     *
     * @return array
     */
    private function getPropertyNameCollectionFactoryContext(ResourceMetadata $resourceMetadata): array
    {
        $attributes = $resourceMetadata->getAttributes();
        $context = [];

        if (isset($attributes['normalization_context'][AbstractNormalizer::GROUPS])) {
            $context['serializer_groups'] = $attributes['normalization_context'][AbstractNormalizer::GROUPS];
        }

        if (isset($attributes['denormalization_context'][AbstractNormalizer::GROUPS])) {
            if (isset($context['serializer_groups'])) {
                foreach ($attributes['denormalization_context'][AbstractNormalizer::GROUPS] as $groupName) {
                    $context['serializer_groups'][] = $groupName;
                }
            } else {
                $context['serializer_groups'] = $attributes['denormalization_context'][AbstractNormalizer::GROUPS];
            }
        }

        return $context;
    }

    /**
     * Gets the range of the property.
     *
     * @param PropertyMetadata $propertyMetadata
     *
     * @return string|null
     */
    private function getRange(PropertyMetadata $propertyMetadata)
    {
        $jsonldContext = $propertyMetadata->getAttributes()['jsonld_context'] ?? [];

        if (isset($jsonldContext['@type'])) {
            return $jsonldContext['@type'];
        }

        if (null === $type = $propertyMetadata->getType()) {
            return null;
        }

        if ($type->isCollection() && null !== $collectionType = $type->getCollectionValueType()) {
            $type = $collectionType;
        }

        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_STRING:
                return 'xmls:string';
            case Type::BUILTIN_TYPE_INT:
                return 'xmls:integer';
            case Type::BUILTIN_TYPE_FLOAT:
                return 'xmls:decimal';
            case Type::BUILTIN_TYPE_BOOL:
                return 'xmls:boolean';
            case Type::BUILTIN_TYPE_OBJECT:
                if (null === $className = $type->getClassName()) {
                    return null;
                }

                if (is_a($className, \DateTimeInterface::class, true)) {
                    return 'xmls:dateTime';
                }

                if ($this->resourceClassResolver->isResourceClass($className)) {
                    $resourceMetadata = $this->resourceMetadataFactory->create($className);

                    return $resourceMetadata->getIri() ?? "#{$resourceMetadata->getShortName()}";
                }
                break;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return self::FORMAT === $format && $data instanceof Documentation;
    }
}
