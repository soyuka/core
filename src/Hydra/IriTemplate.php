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

namespace ApiPlatform\Hydra;

final class IriTemplate
{
    public const TYPE = 'IriTemplate';

    public function __construct(
        public string $template,
        public string $variableRepresentation,
        /** @var IriTemplateMapping[] */
        public array $mapping = [],
    ) {
    }
}
