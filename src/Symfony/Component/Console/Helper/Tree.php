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
 */
class Tree implements \RecursiveIterator
{
    private TreeStyle $style;

    private int $position = 0;

    public function __construct(
        private readonly OutputInterface $output,
        private readonly TreeNode $node,
        ?TreeStyle $style = null,
    ) {
        $this->style = $style ?? TreeStyle::default();
    }

    public function getStyle(): TreeStyle
    {
        return $this->style;
    }

    public function setStyle(TreeStyle $style): self
    {
        $this->style = $style;

        return $this;
    }

    public function current(): TreeNode
    {
        return $this->node->getChildren()[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->node->getChildren()[$this->position]);
    }

    public function hasChildren(): bool
    {
        return !empty($this->current()->getChildren());
    }

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

        $treeIterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_LEFT, $this->style->getPrefixLeft());
        $treeIterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_HAS_NEXT, $this->style->getPrefixMidHasNext());
        $treeIterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_LAST, $this->style->getPrefixMidLast());
        $treeIterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_HAS_NEXT, $this->style->getPrefixEndHasNext());
        $treeIterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_LAST, $this->style->getPrefixEndLast());
        $treeIterator->setPrefixPart(\RecursiveTreeIterator::PREFIX_RIGHT, $this->style->getPrefixRight());

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
