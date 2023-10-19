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
 * @internal
 */
class TraceableImportMapRenderer implements ImportMapRendererInterface
{
    private array $entryPointNames = [];

    public function __construct(private ImportMapRendererInterface $importMapRenderer)
    {
    }

    public function render(array|string $entryPointNames, array $attributes = []): string
    {
        $this->entryPointNames = (array) $entryPointNames;

        return $this->importMapRenderer->render($entryPointNames, $attributes);
    }

    public function wasImportMapRendered(): bool
    {
        return isset($this->entryPointNames);
    }

    public function getEntryPointNames(): array
    {
        return $this->entryPointNames;
    }
}
