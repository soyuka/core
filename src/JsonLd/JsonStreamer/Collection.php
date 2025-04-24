<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use Symfony\Component\JsonStreamer\Attribute\StreamedName;

/**
 * @template T of object
 */
final class Collection
{
    #[StreamedName('@context')]
    public string $context;

    #[StreamedName('@id')]
    public string $id;

    #[StreamedName('@type')]
    public string $type;

    /**
     * @var list<T>
     */
    public iterable $members;
}
