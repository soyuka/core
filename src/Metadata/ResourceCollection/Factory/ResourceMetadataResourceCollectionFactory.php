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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource;
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
                if ($parentResourceCollection[0] ?? false) {
                    return $parentResourceCollection;
                }
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            }
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $attributes = null;

        if ($attributes = ($resourceMetadata->getAttributes() ?? [] && $this->defaults['attributes'])) {
            foreach ($attributes as $key => $value) {
                if (!isset($attributes[$key])) {
                    $attributes[$key] = $value;
                }
            }
        }

        $resource = new Resource();

        foreach ($attributes as $key => $value) {
            $camelCaseKey = $this->converter->denormalize($key);

            $value = $this->sanitizeValueFromKey($key, $value);

            $resource->{$camelCaseKey} = $value;
        }

        $resource->operations = [];

        foreach ($this->createOperations($resourceMetadata->getItemOperations(), OperationType::ITEM) as $operationName => $operation) {
            $operation->shortName = $resourceMetadata->getShortName();
            $resource->operations[$operationName] = $operation;
        }

        foreach ($this->createOperations($resourceMetadata->getCollectionOperations(), OperationType::COLLECTION) as $operationName => $operation) {
            $operation->shortName = $resourceMetadata->getShortName();
            $resource->operations[$operationName] = $operation;
        }

        $resource->shortName = $resourceMetadata->getShortName();
        $resource->description = $resourceMetadata->getDescription();
        $resource->class = $resourceClass;
        $resource->types = [$resourceMetadata->getIri()];
        $resource->graphQl = $resourceMetadata->getGraphql(); // Transformation à faire ici

        return new ResourceCollection([$resource]);
    }

    private function createOperations(array $operations, string $type): iterable
    {
        foreach ($operations as $operationName => $operation) {
            $newOperation = new Operation(method: $operation['method']);

            if (isset($operation['path'])) {
                $newOperation->uriTemplate = $operation['path'];
                unset($operation['path']);
            }

            foreach ($operation as $operationKey => $operationValue) {
                $camelCaseKey = $this->converter->denormalize($operationKey);

                $operationValue = $this->sanitizeValueFromKey($operationKey, $operationValue);

                $newOperation->{$camelCaseKey} = $operationValue;
            }

            // Juste $type === OperationType::COLLECTION Fail dès qu'on a des post
            // $type === OperationType::COLLECTION && $operationName === 'get' fail UNIQUEMENT pour FileConfigDummy qui est dans api_resources_orm.yaml

            // État d'avancement de ma recherche : en fait ça marche bcp de fois alors que ça devrait pas car le get_collection est toujours recherché via un $operationName
            // qui vaut get et parfois il y a bien déjà un get pour item donc il trouve celui-ci mais c'est pas le bon donc
            // il y a un pb sur les $operationName qu'on envoie dans le buildSchema car ils ont pas les bons noms
            // Mettre un sprintf au milieu
            yield $operationName.($type === OperationType::COLLECTION && $operationName === 'get' ? '_collection' : '') => $newOperation;
        }
    }

    public function sanitizeValueFromKey($key, $value)
    {
        return ((($key === 'identifiers') || ($key === 'validation_groups')) && !is_array($key)) ? [$value] : $value;
    }
}
