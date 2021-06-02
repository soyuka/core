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

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Metadata\Resource;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

final class ResourceMetadataResourceCollectionFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;
    private $resourceMetadataFactory;
    private $defaults;
    private $converter;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->defaults = $defaults + ['attributes' => []];
        $this->converter = new CamelCaseToSnakeCaseNameConverter();
    }

    public function create(string $resourceClass): ResourceCollection
    {
        $parentResourceCollection = null;
        if ($this->decorated) {
            try {
                $parentResourceCollection = $this->decorated->create($resourceClass);
                // dd($parentResourceCollection);
                if ($parentResourceCollection[0] ?? false) {
                    return $parentResourceCollection;
                }
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            }
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        $attributes = null;

        if (null !== $resourceMetadata->getAttributes() || [] !== $this->defaults['attributes']) {
            $attributes = (array) $resourceMetadata->getAttributes();
            foreach ($this->defaults['attributes'] as $key => $value) {
                if (!isset($attributes[$key])) {
                    $attributes[$key] = $value;
                }
            }
        }

        $resource = new Resource();

        foreach ($attributes as $key => $value) {
            $camelCaseKey = $this->converter->denormalize($key);
            if (($key === 'identifiers') || ($key === 'validation_groups')) {
                $value = [$value];
            }
            $resource->{$camelCaseKey} = $value;
        }

        $itemOperations = $this->createOperations($resourceMetadata, OperationType::ITEM);
        $collectionOperations = $this->createOperations($resourceMetadata, OperationType::COLLECTION);

        $operations = $itemOperations + $collectionOperations;

        $resource->shortName = $resourceMetadata->getShortName() ?? 'SHORT_NAME_MANDATORY_MISSING'; // à changer mais sans shortName, le UnderscorePathSegmentNameGenerator.php fail
        $resource->description = $resourceMetadata->getDescription();
        $resource->class = get_class($resourceMetadata); // Pas sûr que ce soit ça
        $resource->types = [$resourceMetadata->getIri()];
        $resource->operations = $operations;
        $resource->graphQl = $resourceMetadata->getGraphql();

        if (!$parentResourceCollection) {
            return new ResourceCollection([$resource]);
        }

        // ON arrive jamais ici popur l'instant
        $resourceCollection = $parentResourceCollection;
        dd('ON ARRIVE JAMAIS ICI ... ou pas??');
        // J'ai fait sauter attributes mais je sais pas ce qu'il fait
        foreach (['shortName', 'description', 'types', 'operations', 'graphql'] as $property) {
            $resourceCollection = $this->createWith($resourceCollection, $property, $annotation->{$property});
        }

        return new ResourceCollection($resource);

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }

    /**
     * Returns the resource from the decorated factory if available or throws an exception.
     *
     * @throws ResourceClassNotFoundException
     */
    private function handleNotFound(?ResourceCollection $parentResourceCollection, string $resourceClass): ResourceCollection
    {
        if (null !== $parentResourceCollection) {
            return $parentResourceCollection;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }

    /**
     * je sais pas ça renvoie 1 tableau
     */
    private function createOperations(ResourceMetadata $resourceMetadata, string $type)
    {
        $operations = [];

        foreach ($resourceMetadata->{'get'.$type.'Operations'}() as $operationName => $operation) {
            $operationType = $operation['method'] ?? $operationName; // For example if we have "get"={ no method attribute here }

            $newOperation = new ('\\ApiPlatform\\Metadata\\'.$operationType)();

            if (!$newOperation) {
                dd('STRANGE EDGE CASE FAILED');
            }

            foreach ($operation as $operationKey => $operationValue) {
                if (($operationKey === 'identifiers') || ($operationKey === 'validation_groups')) {
                    $operationValue = [$operationValue];
                }
                $newOperation->{$this->converter->denormalize($operationKey)} = $operationValue;
            }
            // TODO : Il y a un pb avec l'ancien "path" qui va en extraProperties, à quoi correspond-il dans le nouveau Resource.php ?

            // Il faut traiter ici le $operation comme un array en récupérant son method
            $operations[$operationName.(($type == OperationType::COLLECTION) ? '_collection' : '')] = $newOperation;
        }

        return $operations;
    }
    /**
     * Creates a new instance of resource if the property is not already set.
     */
    private function createWith(ResourceCollection $resourceCollection, string $property, $value): ResourceCollection
    {
        $upperProperty = ucfirst($property);
        $getter = "get$upperProperty";

        if (null !== $resourceCollection->{$getter}()) {
            return $resourceCollection;
        }

        if (null === $value) {
            return $resourceCollection;
        }

        $wither = "with$upperProperty";

        return $resourceCollection->{$wither}($value);
    }
}
