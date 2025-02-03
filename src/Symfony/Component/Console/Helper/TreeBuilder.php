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

use Symfony\Component\Finder\Finder;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class TreeBuilder
{
    public static function fromArray(array $array, ?string $root = null): TreeNode
    {
        $root = new TreeNode($root ?? '');
        self::buildTree($root, $array);

        return $root;
    }

    public static function fromIterator(\Iterator $iterator, ?string $root = null): TreeNode
    {
        $root = new TreeNode($root ?? '');
        self::buildTree($root, iterator_to_array($iterator));

        return $root;
    }

    public static function fromPaths(array $paths): TreeNode
    {
        $root = new TreeNode('Root');

        foreach ($paths as $path) {
            $currentNode = $root;
            foreach ($path as $value) {
                $found = false;
                foreach ($currentNode->getChildren() as $child) {
                    if ($child->getValue() === $value) {
                        $currentNode = $child;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $newNode = new TreeNode($value);
                    $currentNode->addChild($newNode);
                    $currentNode = $newNode;
                }
            }
        }

        return $root;
    }

    public static function buildFromFinder(Finder $finder): TreeNode
    {
        $root = new TreeNode('Root');
        $root->addChild(function () use ($finder) {
            foreach ($finder as $item) {
                if ($item->isDir()) {
                    $dirNode = new TreeNode($item->getFilename());
                    $dirNode->addChild(function () use ($item) {
                        $subFinder = new Finder();
                        $subFinder->in($item->getRealPath())->depth('== 0')->sortByName();
                        yield from self::buildFromFinder($subFinder);
                    });
                    yield $dirNode;
                } else {
                    $filename = $item->getFilename();
                    if (stripos($filename, 'er.php') !== false) {
                        $filename = "<fg=red>$filename</>";
                    }
                    yield new TreeNode($filename);
                }
            }
        });

        return $root;
    }

    private static function buildTree(TreeNode $node, array $array): TreeNode
    {
        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $child = new TreeNode($key);
                self::buildTree($child, $value);
                $node->addChild($child);
            } else {
                $node->addChild(new TreeNode($value));
            }
        }

        return $node;
    }
}
