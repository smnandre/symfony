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
use Symfony\Component\Console\Helper\TreeBuilder;

class TreeBuilderTest extends TestCase
{
    public function testFromArray()
    {
        $array = [
            'Child 1' => [
                'Leaf 1',
            ],
            'Child 2',
        ];

        $root = TreeBuilder::fromArray($array);

        $this->assertCount(2, $root->getChildren());

        $root = TreeBuilder::fromArray($array, '');
        $this->assertSame('', $root->getValue());

        $root = TreeBuilder::fromArray($array, '**');
        $this->assertSame('**', $root->getValue());

        $child1 = $root->getChildren()[0];
        $this->assertSame('Child 1', $child1->getValue());
        $this->assertCount(1, $child1->getChildren());

        $leaf1 = $child1->getChildren()[0];
        $this->assertSame('Leaf 1', $leaf1->getValue());

        $child2 = $root->getChildren()[1];
        $this->assertSame('Child 2', $child2->getValue());
        $this->assertEmpty($child2->getChildren());
    }

    public function testFromArrayWithEmptyArray()
    {
        $root = TreeBuilder::fromArray([]);
        $this->assertEmpty($root->getChildren());
    }

    public function testBuildWithRootValue()
    {
        $tree = TreeBuilder::fromArray([], 'Foo');
        $this->assertEmpty($tree->getChildren());
        $this->assertEquals('Foo', (string) $tree);
    }

    public function testFromPaths()
    {
        $paths = [
            ['Child 1', 'Leaf 1'],
            ['Child 2'],
        ];

        $root = TreeBuilder::fromPaths($paths);

        $this->assertCount(2, $root->getChildren());

        $child1 = $root->getChildren()[0];
        $this->assertSame('Child 1', $child1->getValue());
        $this->assertCount(1, $child1->getChildren());

        $leaf1 = $child1->getChildren()[0];
        $this->assertSame('Leaf 1', $leaf1->getValue());

        $child2 = $root->getChildren()[1];
        $this->assertSame('Child 2', $child2->getValue());
        $this->assertEmpty($child2->getChildren());
    }

    public function testFromRecursiveIterator()
    {
        $recursiveIterator = new \RecursiveArrayIterator([
            'Child 1' => [
                'Leaf 1',
            ],
            'Child 2',
        ]);

        $root = TreeBuilder::fromIterator($recursiveIterator);

        $this->assertCount(2, $root->getChildren());
        $this->assertSame('Child 1', $root->getChildren()[0]->getValue());
        $this->assertSame('Child 2', $root->getChildren()[1]->getValue());
        $this->assertSame('Leaf 1', $root->getChildren()[0]->getChildren()[0]->getValue());
    }
}
