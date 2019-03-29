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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Exception;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Thrown by responses' toArray() method when their content cannot be JSON-decoded.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
final class JsonException extends \JsonException implements TransportExceptionInterface
{
}
