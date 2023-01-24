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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Tests\Fixtures\TestBundle\State\AttributeResourceProvider;
use Symfony\Component\Marshaller\Attribute\Name;

#[ApiResource(
    '/attribute_resources{._format}',
    normalizationContext: ['skip_null_values' => true],
    provider: AttributeResourceProvider::class
)]
#[GetCollection]
#[Post]
final class AttributeResources implements \IteratorAggregate
{
    /**
     * @var AttributeResource[]
     */
    #[Name('hydra:member')]
    public array $collection;

    #[Name('@type')]
    public string $type = 'hydra:Collection';

    #[Name('hydra:totalItems')]
    public int $totalItems = 0;

    public function __construct(AttributeResource ...$collection)
    {
        $this->collection = $collection;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->collection);
    }
}
