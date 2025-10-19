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

namespace ApiPlatform\Mcp\Factory;

/**
 * Creates MCP capability definitions from API Platform operations.
 *
 * @internal
 *
 * @deprecated
 */
final readonly class McpOperationFactory implements McpCapabilityFactoryInterface
{
    public function __construct()
    {
    }

    /**
     * Creates and yields MCP capability definitions.
     *
     * @return \Generator<array{type: string, definition: array}>
     */
    public function create(): \Generator
    {
        yield from [];
    }
}
