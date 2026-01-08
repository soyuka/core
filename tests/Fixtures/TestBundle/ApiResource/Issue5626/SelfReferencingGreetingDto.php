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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5626;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5626\SelfReferencingGreeting;
use Symfony\Component\Serializer\Attribute\Groups;

class SelfReferencingGreetingDto
{
    public function __construct(
        #[Groups(['advanced'])]
        public SelfReferencingGreeting $greeting,

        #[Groups(['advanced'])]
        public int $viewCount
    ) {
    }
}
