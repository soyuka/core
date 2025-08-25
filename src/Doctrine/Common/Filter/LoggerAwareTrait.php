<?php

namespace ApiPlatform\Doctrine\Common\Filter;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait LoggerAwareTrait
{
    private ?LoggerInterface $logger = null;

    public function hasLogger(): bool
    {
        return $this->logger instanceof LoggerInterface;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger ??= new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}

