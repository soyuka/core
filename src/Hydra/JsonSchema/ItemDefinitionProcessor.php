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

namespace ApiPlatform\Hydra\JsonSchema;

use ApiPlatform\JsonSchema\Generator\ApiPlatformDefinitionPolicy;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\DefinitionPolicy\DefinitionParent;
use Symfony\Component\JsonSchema\DefinitionProcessor\DefinitionProcessorInterface;

final class ItemDefinitionProcessor implements DefinitionProcessorInterface
{
    public function __construct(
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly ApiPlatformDefinitionPolicy $definitionPolicy,
    ) {
    }

    public function process(array $definition, string $class, Configuration $config, ?DefinitionParent $parent): array
    {
        if (null === $parent || 'jsonld' !== $config->format || !$this->resourceClassResolver->isResourceClass($class)) {
            return $definition;
        }

        $baseName = $this->definitionPolicy->resolveGenId($parent) ? SchemaFactory::ITEM_BASE_SCHEMA_NAME : SchemaFactory::ITEM_WITHOUT_ID_BASE_SCHEMA_NAME;
        $item = ['allOf' => [['$ref' => $config->dialect->refPath.$baseName], $definition]];

        if (isset($definition['description'])) {
            $item['description'] = $definition['description'];
            unset($item['allOf'][1]['description']);
        }

        return $item;
    }
}
