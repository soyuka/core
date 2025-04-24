<?php

declare(strict_types=1);

namespace ApiPlatform\JsonLd\JsonStreamer;

use ApiPlatform\Hydra\IriTemplate;
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

    // public IriTemplate $search;

    /**
     * @var list<Foo>
     */
    public array $member;
}
