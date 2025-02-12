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

use Symfony\Component\Console\Output\OutputInterface;

/**
 * The TreeHelper class provides methods to display tree-like structures.
 *
 * @author Simon André <smn.andre@gmail.com>
 *
 * @implements \RecursiveIterator<int, TreeNode>
 */
final class TreeHelper implements \RecursiveIterator
{
    private readonly TreeStyle $style;

    private readonly TreeNode $node;

    private \Iterator $childrenIterator;

    public function __construct(
        private readonly OutputInterface $output,
        ?TreeNode $node = null,
        ?TreeStyle $style = null,
    ) {
        $this->node = $node ?? new TreeNode();
        $this->style = $style ?? TreeStyle::default();
        $this->childrenIterator = new \IteratorIterator($this->node->getChildren());
        $this->childrenIterator->rewind();
    }

    public static function create(OutputInterface $output, iterable $values, ?string $root = null, ?TreeStyle $style = null): self
    {
        return new self($output, TreeNode::fromValues($values, new TreeNode($root ?? '')), $style);
    }

    public function current(): TreeNode
    {
        return $this->childrenIterator->current();
    }

    public function key(): int
    {
        return $this->childrenIterator->key();
    }

    public function next(): void
    {
        $this->childrenIterator->next();
    }

    public function rewind(): void
    {
        $this->childrenIterator->rewind();
    }

    public function valid(): bool
    {
        return $this->childrenIterator->valid();
    }

    public function hasChildren(): bool
    {
        if (null === $current = $this->current()) {
            return false;
        }

        foreach ($current->getChildren() as $child) {
            return true;
        }

        return false;
    }

    public function getChildren(): \RecursiveIterator
    {
        return new self($this->output, $this->current(), $this->style);
    }

    /**
     * Recursively renders the tree to the output, applying the tree style.
     */
    public function render(): void
    {
        $treeIterator = new \RecursiveTreeIterator($this);

        $this->style->applyPrefixes($treeIterator);

        $this->output->writeln($this->node->getValue());

        $visited = new \SplObjectStorage();
        foreach ($this->traverseWithCycleDetection($treeIterator, $visited) as $line) {
            $this->output->writeln($line);
        }
    }

    /**
     * Traverses the tree with cycle detection.
     *
     * @return \Traversable<int, string>
     */
    private function traverseWithCycleDetection(\RecursiveTreeIterator $iterator, \SplObjectStorage $visited): \Traversable
    {
        foreach ($iterator as $node) {
            $currentNode = $node instanceof TreeNode ? $node : $iterator->getInnerIterator()->current();
            if ($visited->contains($currentNode)) {
                throw new \LogicException(\sprintf('Cycle detected at node: "%s".', $currentNode->getValue()));
            }
            $visited->attach($currentNode);

            yield $node;
        }
    }
}
