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

    /**
     * @var array<self>
     */
    private array $children;

    public function __construct(
        ?string $value = null,
        ?self $parent = null,
        array $children = [],
    ) {
        $this->value = $value ?? '';
        if ($parent) {
            $parent->addChild($this);
        }
        foreach ($children as $child) {
            $this->addChild($child);
        }
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

    /**
     * @return iterable<TreeNode>
     */
    public function getChildren(): array
    {
        return $this->children;
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
