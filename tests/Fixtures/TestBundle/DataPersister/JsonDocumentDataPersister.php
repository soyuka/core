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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\JsonDocument;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

class JsonDocumentDataPersister implements ContextAwareDataPersisterInterface
{
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        return $data instanceof JsonDocument;
    }

    /**
     * {@inheritdoc}
     */
    public function persist($data, array $context = [])
    {
        /** @var EntityManager */
        $em = $this->registry->getManagerForClass(JsonDocument::class);
        $data->misc = clone $data->misc;
        $em->persist($data);
        $em->flush();

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($data, array $context = [])
    {
        return null;
    }
}
