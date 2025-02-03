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
use Symfony\Component\Finder\Finder;

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

        $this->assertSame(2, iterator_count($root->getChildren()));

        $root = TreeBuilder::fromArray($array, '');
        $this->assertSame('', $root->getValue());

        $root = TreeBuilder::fromArray($array, '**');
        $this->assertSame('**', $root->getValue());

        $child1 = iterator_to_array($root->getChildren())[0];
        $this->assertSame('Child 1', $child1->getValue());
        $this->assertSame(1, iterator_count($child1->getChildren()));

        $leaf1 = iterator_to_array($child1->getChildren())[0];
        $this->assertSame('Leaf 1', $leaf1->getValue());

        $child2 = iterator_to_array($root->getChildren())[1];
        $this->assertSame('Child 2', $child2->getValue());
        $this->assertSame(0, iterator_count($child2->getChildren()));
    }

    public function testFromArrayWithEmptyArray()
    {
        $root = TreeBuilder::fromArray([]);
        $this->assertSame(0, iterator_count($root->getChildren()));
    }

    public function testBuildWithRootValue()
    {
        $tree = TreeBuilder::fromArray([], 'Foo');
        $this->assertSame(0, iterator_count($tree->getChildren()));
        $this->assertSame('Foo', (string) $tree);
    }

    public function testFromPaths()
    {
        $paths = [
            ['Child 1', 'Leaf 1'],
            ['Child 2'],
        ];

        $root = TreeBuilder::fromPaths($paths);

        $this->assertSame(2, iterator_count($root->getChildren()));

        $children = iterator_to_array($root->getChildren());

        $child1 = $children[0];
        $this->assertSame('Child 1', $child1->getValue());
        $this->assertSame(1, iterator_count($child1->getChildren()));

        $leaf1 = iterator_to_array($child1->getChildren())[0];
        $this->assertSame('Leaf 1', $leaf1->getValue());

        $child2 = $children[1];
        $this->assertSame('Child 2', $child2->getValue());
        $this->assertSame(0, iterator_count($child2->getChildren()));
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

        $this->assertSame(2, iterator_count($root->getChildren()));
        $this->assertSame('Child 1', iterator_to_array($root->getChildren())[0]->getValue());
        $this->assertSame('Child 2', iterator_to_array($root->getChildren())[1]->getValue());
    }

    public function testFromFinder()
    {
        $tempDir = sys_get_temp_dir().'/test_tree_builder/'.rand(0, 999999);
        mkdir($tempDir.'/dir1', 0777, true);
        mkdir($tempDir.'/dir2', 0777, true);

        file_put_contents($tempDir.'/file1.php', '');
        file_put_contents($tempDir.'/dir1/file2er.php', '');
        file_put_contents($tempDir.'/dir2/file3.php', '');

        $finder = new Finder();
        $finder->in($tempDir)->depth('== 0')->sortByName();

        $root = TreeBuilder::buildFromFinder($finder);

        $this->assertSame('Root', $root->getValue());
        $children = iterator_to_array($root->getChildren());
        $this->assertCount(3, $children);
        $this->assertSame('dir1', $children[0]->getValue());
        $this->assertSame('dir2', $children[1]->getValue());
        $this->assertSame('file1.php', $children[2]->getValue());

        $dir1Children = iterator_to_array($children[0]->getChildren());
        $this->assertCount(1, $dir1Children);
        $this->assertSame('<fg=red>file2er.php</>', $dir1Children[0]->getValue());

        $dir2Children = iterator_to_array($children[1]->getChildren());
        $this->assertCount(1, $dir2Children);
        $this->assertSame('file3.php', $dir2Children[0]->getValue());

        // Clean up
        array_map('unlink', glob("$tempDir/*.*"));
        array_map('unlink', glob("$tempDir/dir1/*.*"));
        array_map('unlink', glob("$tempDir/dir2/*.*"));
        rmdir($tempDir.'/dir1');
        rmdir($tempDir.'/dir2');
        rmdir($tempDir);
    }
}
