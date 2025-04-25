<?php

declare(strict_types=1);

namespace ApiPlatform\Hydra;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465\Foo;
use Symfony\Component\JsonStreamer\Attribute\StreamedName;

final class Collection
{
    #[StreamedName('@context')]
    public ?string $context = null;

    #[StreamedName('@id')]
    public CollectionId $id = CollectionId::VALUE;

    #[StreamedName('@type')]
    public string $type = 'Collection';

    public IriTemplate $search;

    public ?int $totalItems = null;

    /**
     * @var list<Foo>
     */
    public array $member;
}
