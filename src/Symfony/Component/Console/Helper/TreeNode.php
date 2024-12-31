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
final class TreeNode implements \Countable, \IteratorAggregate, \Stringable
{
    private readonly string $value;

    private ?self $parent;

    /**
     * @var array<self>
     */
    private array $children = [];

    public function __construct(
        ?string $value = null,
        ?self $parent = null,
        array $children = [],
    ) {
        $this->value = $value ?? '';
        if (null !== $parent) {
            $parent->addChild($this);
        }
        foreach ($children as $child) {
            $this->addChild($child);
        }
        $this->parent = $parent;
        $this->children = $children;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function addChild(self|string $node): self
    {
        if (\is_string($node)) {
            $node = new self($node, $this);
        }

        $this->children[] = $node;

        return $this;
    }

    public function addChildren(self|\Iterator ...$children): self
    {
        foreach ($children as $child) {
            if ($child instanceof self) {
                $this->addChild($child);
            } elseif ($child instanceof \Iterator) {
                foreach ($child as $node) {
                    $this->addChild(new self($node));
                }
            }
        }

        return $this;
    }

    /**
     * @return iterable<TreeNode>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function isLeaf(): bool
    {
        return empty($this->children);
    }

    /**
     * @return \RecursiveArrayIterator<TreeNode>
     */
    public function getIterator(): \Traversable
    {
        return new class($this->children) extends \RecursiveArrayIterator {
            public function hasChildren(): bool
            {
                return !$this->current()->hasChildren();
            }

            public function getChildren(): self
            {
                return new self($this->current()->getChildren());
            }
        };
    }

    public function count(): int
    {
        return \count($this->children);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
