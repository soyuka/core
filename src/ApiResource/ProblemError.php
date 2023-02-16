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

use ApiPlatform\Exception\ProblemExceptionInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ErrorResource;

#[ErrorResource(uriTemplate: '/problems/{status}', provider: 'api_platform.state_provider.default_problem_error')]
final class ProblemError extends \Exception implements ProblemExceptionInterface
{
    public function __construct(
        private readonly string $title,
        private readonly string $detail,
        #[ApiProperty(identifier: true)] private readonly int $status,
        public readonly array $trace,
        private string $instance = '',
        private string $type = '',
    ) {
        parent::__construct();
    }

    public static function createFromException(\Exception|\Throwable $exception, int $statusCode, string $instance = '', string $type = ''): self
    {
        $problem = new self($exception->getMessage(), $exception->getMessage(), $statusCode, $exception->getTrace());
        $problem->setType($type);
        $problem->setInstance($instance);

        return $problem;
    }

    public function getInstance(): string
    {
        return $this->instance;
    }

    public function setInstance(?string $instance): void
    {
        $this->instance = $instance;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }
}
