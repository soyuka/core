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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Hydra\Collection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;

class AttributeResourceProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AttributeResource|Collection
    {
        if (isset($uriVariables['identifier'])) {
            return new AttributeResource($uriVariables['identifier'], 'Foo');
        }
        
        return new Collection(new AttributeResource(1, 'Foo'), new AttributeResource(2, 'Bar'));
    }
}
