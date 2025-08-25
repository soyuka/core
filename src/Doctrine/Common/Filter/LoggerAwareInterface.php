<?php

namespace ApiPlatform\Doctrine\Common\Filter;

use Psr\Log\LoggerInterface;

interface LoggerAwareInterface
{
    public function hasLogger(): bool;

    public function getLogger(): LoggerInterface;

    public function setLogger(LoggerInterface $logger): void;
}

