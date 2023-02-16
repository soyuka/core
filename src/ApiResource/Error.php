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

namespace ApiPlatform\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ErrorResource;

#[ErrorResource(uriTemplate: '/errors/{statusCode}', provider: 'api_platform.state_provider.default_error')]
final class Error extends \Exception
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        #[ApiProperty(identifier: true)] public readonly int $statusCode,
        public readonly array $trace
    ) {
        parent::__construct();
    }

    public static function createFromException(\Exception|\Throwable $exception, int $statusCode): self
    {
        return new self($exception->getMessage(), $exception->getMessage(), $statusCode, $exception->getTrace());
    }
}
