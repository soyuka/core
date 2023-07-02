<?php

namespace ApiPlatform\JsonLd;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Context
{
    /**
     * @param array<string ,mixed> $context
     */
    public function __construct(#[SerializedName('@context')] public array $context) {}
}
