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

namespace ApiPlatform\Core\Bridge\Rector\Parser;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class TransformApiSubresourceVisitor extends NodeVisitorAbstract
{
    private $subresourceMetadata;

    public function __construct($subresourceMetadata)
    {
        $this->subresourceMetadata = $subresourceMetadata;
    }

    public function enterNode(Node $node)
    {
        $operationToCreate = $this->subresourceMetadata['collection'] ? GetCollection::class : Get::class;
        $operationUseStatementNeeded = true;

        if ($node instanceof Node\Stmt\Namespace_) {
            foreach ($node->stmts as $stmt) {
                if (!$stmt instanceof Node\Stmt\Use_) {
                    break;
                }

                $useStatement = implode('\\', $stmt->uses[0]->name->parts);
                if ($useStatement === $operationToCreate) {
                    $operationUseStatementNeeded = false;
                    break;
                }
            }
            if ($operationUseStatementNeeded) {
                array_unshift(
                    $node->stmts,
                    new Node\Stmt\Use_([
                        new Node\Stmt\UseUse(
                            new Node\Name(
                                $this->subresourceMetadata['collection'] ? GetCollection::class : Get::class
                            )
                        ),
                    ])
                );
            }
        }

        if ($node instanceof Node\Stmt\Class_) {
            $identifiersNodeItems = [];
            foreach ($this->subresourceMetadata['identifiers'] as $identifier => $resource) {
                $identifiersNodeItems[] = new Node\Expr\ArrayItem(
                    new Node\Expr\Array_(
                        [
                            new Node\Expr\ArrayItem(
                                new Node\Expr\ClassConstFetch(
                                    new Node\Name(
                                        ($resource[0] === $this->subresourceMetadata['resource_class']) ? 'self' : '\\'.$resource[0]
                                    ),
                                    'class'
                                )
                            ),
                            new Node\Expr\ArrayItem(
                                new Node\Scalar\String_($resource[1])
                            ),
                        ],
                        [
                            'kind' => Node\Expr\Array_::KIND_SHORT,
                        ]
                    ),
                    new Node\Scalar\String_($identifier)
                );
            }

            $identifiersNode = new Node\Expr\Array_($identifiersNodeItems, ['kind' => Node\Expr\Array_::KIND_SHORT]);

            $apiResourceAttribute =
                new Node\AttributeGroup([
                    new Node\Attribute(
                        new Node\Name('\\ApiPlatform\\Metadata\\ApiResource'),
                        [
                            new Node\Arg(
                                new Node\Scalar\String_(str_replace('.{_format}', '', $this->subresourceMetadata['path'])),
                                false,
                                false,
                                [],
                                new Node\Identifier('uriTemplate')
                            ),
                            new Node\Arg(
                                $identifiersNode,
                                false,
                                false,
                                [],
                                new Node\Identifier('identifiers')
                            ),
                            new Node\Arg(
                                new Node\Scalar\LNumber(200),
                                false,
                                false,
                                [],
                                new Node\Identifier('status')
                            ),
                            new Node\Arg(
                                new Node\Expr\ArrayItem(
                                    new Node\Expr\Array_(
                                        [
                                            new Node\Expr\ArrayItem(
                                                new Node\Scalar\LNumber(1),
                                                new Node\Scalar\String_('legacy_subresource_behavior')
                                            ),
                                        ],
                                        [
                                            'kind' => Node\Expr\Array_::KIND_SHORT,
                                        ]
                                    )
                                ),
                                false,
                                false,
                                [],
                                new Node\Identifier('extraProperties')
                            ),
                        ]
                    ),
                ]);

            $operationAttribute =
                new Node\AttributeGroup([
                    new Node\Attribute(
                        new Node\Name($this->subresourceMetadata['collection'] ? 'GetCollection' : 'Get')
                    ),
                ]);

            $node->attrGroups[] = $apiResourceAttribute;
            $node->attrGroups[] = $operationAttribute;
        }
    }
}
