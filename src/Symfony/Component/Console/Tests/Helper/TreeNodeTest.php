<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\TreeNode;

class TreeNodeTest extends TestCase
{
    public function testNodeInitialization(): void
    {
        $node = new TreeNode('Root');
        $this->assertSame('Root', $node->getValue());
        $this->assertEmpty($node->getChildren());
        $this->assertTrue($node->isLeaf());
    }

    public function testAddingChildren(): void
    {
        $root = new TreeNode('Root');
        $child = new TreeNode('Child');

        $root->addChild($child);

        $this->assertCount(1, $root->getChildren());
        $this->assertFalse($root->isLeaf());
        $this->assertSame($child, $root->getChildren()[0]);
    }

    public function testRecursiveStructure(): void
    {
        $root = new TreeNode('Root');
        $child1 = new TreeNode('Child 1');
        $child2 = new TreeNode('Child 2');
        $leaf1 = new TreeNode('Leaf 1');

        $child1->addChild($leaf1);
        $root->addChild($child1);
        $root->addChild($child2);

        $this->assertCount(2, $root->getChildren());
        $this->assertSame($leaf1, $child1->getChildren()[0]);
    }
}
