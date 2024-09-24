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

namespace ApiPlatform\Serializer\Mapping\Loader;

use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Attribute\SerializedPath;
use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

/**
 * Loader for PHP attributes using ApiProperty.
 */
final class PropertyMetadataLoader implements LoaderInterface
{
    public function __construct(private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly ?LoaderInterface $decorated = null)
    {
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        $ret = $this->decorated?->loadClassMetadata($classMetadata);
        $attributesMetadata = $classMetadata->getAttributesMetadata();

        if ($classMetadata->getReflectionClass()->isAbstract()) {
            return $ret ?? false;
        }

        foreach ($this->propertyNameCollectionFactory->create($resourceClass = $classMetadata->getName()) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);
            if (!($attributes = $propertyMetadata->getSerialize() ?? [])) {
                continue;
            }

            if (!isset($attributesMetadata[$propertyName])) {
                $attributesMetadata[$propertyName] = new AttributeMetadata($propertyName);
                $classMetadata->addAttributeMetadata($attributesMetadata[$propertyName]);
            }

            foreach ($attributes as $annotation) {
                if ($annotation instanceof Groups) {
                    foreach ($annotation->getGroups() as $group) {
                        $attributesMetadata[$propertyName]->addGroup($group);
                    }
                } elseif ($annotation instanceof MaxDepth) {
                    $attributesMetadata[$propertyName]->setMaxDepth($annotation->getMaxDepth());
                } elseif ($annotation instanceof SerializedName) {
                    $attributesMetadata[$propertyName]->setSerializedName($annotation->getSerializedName());
                } elseif ($annotation instanceof SerializedPath) {
                    $attributesMetadata[$propertyName]->setSerializedPath($annotation->getSerializedPath());
                } elseif ($annotation instanceof Ignore) {
                    $attributesMetadata[$propertyName]->setIgnore(true);
                } elseif ($annotation instanceof Context) {
                    $this->setAttributeContextsForGroups($annotation, $attributesMetadata[$propertyName]);
                }
            }
        }

        return true;
    }

    private function setAttributeContextsForGroups(Context $annotation, AttributeMetadataInterface $attributeMetadata): void
    {
        if ($annotation->getContext()) {
            $attributeMetadata->setNormalizationContextForGroups($annotation->getContext(), $annotation->getGroups());
            $attributeMetadata->setDenormalizationContextForGroups($annotation->getContext(), $annotation->getGroups());
        }

        if ($annotation->getNormalizationContext()) {
            $attributeMetadata->setNormalizationContextForGroups($annotation->getNormalizationContext(), $annotation->getGroups());
        }

        if ($annotation->getDenormalizationContext()) {
            $attributeMetadata->setDenormalizationContextForGroups($annotation->getDenormalizationContext(), $annotation->getGroups());
        }
    }
}
