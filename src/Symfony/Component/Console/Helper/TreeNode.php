<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Helper;

/**
 * @implements \IteratorAggregate<TreeNode>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class TreeNode implements \Countable, \IteratorAggregate
{
    /**
     * @var array<TreeNode|callable(): \Generator>
     */
    private array $children = [];

    public function __construct(
        private readonly string $value = '',
        ?self $parent = null,
        iterable $children = [],
    ) {
        if ($parent) {
            $parent->addChild($this);
        }

        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    /**
     * @param array<string> $array
     */
    public static function fromArray(array $array, ?self $node = null): self
    {
        $node ??= new self();
        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $node->addChild(self::fromArray($value, new self($key)));
            } else {
                $node->addChild(new self($value));
            }
        }

        return $node;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function addChild(self|string|callable $node): self
    {
        if (\is_string($node)) {
            $node = new self($node, $this);
        }

        $this->children[] = $node;

        return $this;
    }

    /**
     * @return \Traversable<TreeNode>
     */
    public function getChildren(): \Traversable
    {
        foreach ($this->children as $child) {
            if (\is_callable($child)) {
                yield from $child();
            } elseif ($child instanceof self) {
                yield $child;
            }
        }
    }

    /**
     * @return \Traversable<TreeNode>
     */
    public function getIterator(): \Traversable
    {
        return $this->getChildren();
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->getChildren() as $child) {
            ++$count;
        }

        return $count;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
