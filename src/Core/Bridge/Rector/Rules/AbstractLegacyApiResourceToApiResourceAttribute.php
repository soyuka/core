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

namespace ApiPlatform\Core\Bridge\Rector\Rules;

use ApiPlatform\Core\Bridge\Rector\Resolver\OperationClassResolver;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use PhpParser\Node\AttributeGroup;
use Rector\Core\Rector\AbstractRector;
use Rector\PhpAttribute\Printer\PhpAttributeGroupFactory;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\String\UnicodeString;

/**
 * @experimental
 */
abstract class AbstractLegacyApiResourceToApiResourceAttribute extends AbstractRector
{
    use DeprecationMetadataTrait;

    protected PhpAttributeGroupFactory $phpAttributeGroupFactory;

    protected array $operationTypes = ['collectionOperations', 'itemOperations', 'graphql'];

    protected function normalizeOperations(array $operations, string $type): array
    {
        foreach (array_reverse($operations) as $name => $arguments) {
            /*
             * Case of custom action, ex:
             * itemOperations={
             *     "get_by_isbn"={"method"="GET", "path"="/books/by_isbn/{isbn}.{_format}", "requirements"={"isbn"=".+"}, "identifiers"="isbn"}
             * }
             */
            if (\is_array($arguments)) {
                // add operation name
                $arguments = ['name' => $name] + $arguments;
                foreach ($arguments as $key => $argument) {
                    // camelize argument name
                    $camelizedKey = (string) (new UnicodeString($key))->camel();
                    if ($key === $camelizedKey) {
                        continue;
                    }
                    $arguments[$camelizedKey] = $argument;
                    unset($arguments[$key]);
                }
                // Prevent wrong order of operations
                unset($operations[$name]);
            }

            /*
             * Case of default action, ex:
             * collectionOperations={"get", "post"},
             * itemOperations={"get", "put", "delete"},
             * graphql={"create", "delete"}
             */
            if (\is_string($arguments)) {
                unset($operations[$name]);
                $name = $arguments;
                $arguments = ('graphql' !== $type) ? [] : ['name' => $arguments];
            }

            if (isset($arguments['name']) && \in_array(strtolower($arguments['name']), ('graphql' !== $type) ? ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'] : ['item_query', 'collection_query', 'mutation'], true)) {
                unset($arguments['name']);
            }

            $operations[$name] = $arguments;
        }

        return $operations;
    }

    protected function createOperationAttributeGroup(string $type, string $name, array $arguments): AttributeGroup
    {
        $operationClass = OperationClassResolver::resolve($name, $type, $arguments);

        $camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        // Replace old attributes with new attributes
        foreach ($arguments as $key => $value) {
            [$updatedKey, $updatedValue] = $this->getKeyValue($camelCaseToSnakeCaseNameConverter->normalize($key), $value);
            unset($arguments[$key]);
            $arguments[$updatedKey] = $updatedValue;
        }
        // remove unnecessary argument "method" after resolving the operation class
        if (isset($arguments['method'])) {
            unset($arguments['method']);
        }

        return $this->phpAttributeGroupFactory->createFromClassWithItems($operationClass, $arguments);
    }
}
