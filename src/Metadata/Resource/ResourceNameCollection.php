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

namespace ApiPlatform\Core\Metadata\Resource;

/**
 * A collection of resource class names.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ResourceNameCollection implements \IteratorAggregate, \Countable
{
    private $classes;
    private array $newClasses = [];

    /**
     * @param string[] $classes
     */
    public function __construct(array $classes = [], array $newClasses = [])
    {
        $this->classes = $classes;
        $this->newClasses = $newClasses;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Traversable<string>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->classes);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->classes);
    }

    public function isNewClass(string $class) {
        return \in_array($class, $this->newClasses);
    }
}
