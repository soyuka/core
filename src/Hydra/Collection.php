<?php

declare(strict_types=1);

namespace ApiPlatform\Hydra;

use Symfony\Component\JsonStreamer\Attribute\StreamedName;

/**
 * @template T
 */
final class Collection
{
    #[StreamedName('@context')]
    public string $context = 'VIRTUAL';

    #[StreamedName('@id')]
    public CollectionId $id = CollectionId::VALUE;

    #[StreamedName('@type')]
    public string $type = 'Collection';

    public ?IriTemplate $search = null;

    public ?float $totalItems = null;

    /**
     * @var list<T>
     */
    public array $member;
}
