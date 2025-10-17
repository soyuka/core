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

namespace ApiPlatform\Mcp\Metadata;

/**
 * @internal
 */
final class McpResourceTemplate implements McpCapabilityInterface
{
    public function __construct(
        public ?string $uriTemplate = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $mimeType = null,
    ) {
    }
}
