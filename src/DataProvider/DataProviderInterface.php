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

namespace ApiPlatform\Core\DataProvider;

/**
 * Retrieves data from a persistence layer.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface DataProviderInterface
{
    /**
     * Retrieves data.
     *
     * @return object|array|null
     */
    public function retrieve(string $resourceClass, array $identifiers = [], array $context = [])
    {
        if (null === $identifiers['id']) {
            return [];
        }

        return new User();
    }

    public function supports(string $resourceClass, array $identifiers = [], array $context = []): bool
    {
        return User::class === $resourceClass;
    }
}
