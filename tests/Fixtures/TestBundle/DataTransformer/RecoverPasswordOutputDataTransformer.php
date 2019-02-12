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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\User as UserDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Dto\RecoverPasswordOutput;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\User;

final class RecoverPasswordOutputDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($object, array $context = [])
    {
        if (!$object instanceof User && !$object instanceof UserDocument) {
            throw new \InvalidArgumentException();
        }

        $output = new RecoverPasswordOutput();
        $output->user = $object;

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($object, array $context = []): bool
    {
        return ($object instanceof User || $object instanceof UserDocument) && RecoverPasswordOutput::class === ($context['output']['class'] ?? null);
    }
}
