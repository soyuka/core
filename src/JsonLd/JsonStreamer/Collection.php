<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use ApiPlatform\Hydra\IriTemplate;
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

    public IriTemplate $search;

    /**
     * @var iterable<T>
     */
    public iterable $member;
}
