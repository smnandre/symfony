<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\AssetMapper\ImportMap;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
interface ImportMapRendererInterface
{
    /**
     * Renders the importmap, preload tags & CSS link tags.
     *
     * @param string|string[] $entryPointNames
     * @param array<string, string> $attributes
     */
    public function render(string|array $entryPointNames, array $attributes = []): string;
}
