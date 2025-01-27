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
 * @author Simon André <smn.andre@gmail.com>
 *
 * @implements \RecursiveIterator<int, TreeNode>
 */
final class Tree implements \RecursiveIterator
{
    private readonly TreeStyle $style;

    private int $position = 0;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly TreeNode $node,
        ?TreeStyle $style = null,
    ) {
        $this->style = $style ?? TreeStyle::default();
    }

    /**
     * @internal
     */
    public function current(): TreeNode
    {
        return $this->node->getChildren()[$this->position];
    }

    /**
     * @internal
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * @internal
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * @internal
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * @internal
     */
    public function valid(): bool
    {
        return isset($this->node->getChildren()[$this->position]);
    }

    /**
     * @internal
     */
    public function hasChildren(): bool
    {
        return [] !== $this->current()->getChildren();
    }

    /**
     * @internal
     */
    public function getChildren(): \RecursiveIterator
    {
        return new self($this->output, $this->current(), $this->style);
    }

    public function render(): void
    {
        $visited = [];
        $this->detectCycle($this->node, $visited);

        $treeIterator = new \RecursiveTreeIterator(
            $this,
            \RecursiveIteratorIterator::CHILD_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD,
        );

        $this->style->applyPrefixes($treeIterator);

        $this->output->writeln($this->node->getValue());

        foreach ($treeIterator as $line) {
            $this->output->writeln($line);
        }
    }

    private function detectCycle(TreeNode $node, array &$visited): void
    {
        $nodeId = spl_object_id($node);
        if (isset($visited[$nodeId])) {
            throw new \LogicException('Cycle detected in the tree structure.');
        }

        $visited[$nodeId] = true;

        foreach ($node->getChildren() as $child) {
            $this->detectCycle($child, $visited);
        }

        unset($visited[$nodeId]);
    }
}
