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

namespace ApiPlatform\Core\Attributes;

/**
 * Resource attribute
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Resource
{
    // public Operation[] $operations;
    // uriTemplate => génére URI pour les operations /users/{id} 
    // rdfTypes => IRI en ce moment, reosurces, types et propriétés en array 
    // utiliser un trait pour ca
    // public array $extraProperties;
    use AttributeTrait;
}
