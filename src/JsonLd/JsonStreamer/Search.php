<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use Symfony\Component\JsonStreamer\Attribute\StreamedName;

final class Search
{
    #[StreamedName('@type')]
    public string $type;
}
