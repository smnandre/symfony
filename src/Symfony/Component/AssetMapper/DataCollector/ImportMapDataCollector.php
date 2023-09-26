<?php

namespace Symfony\Component\AssetMapper\DataCollector;

use Symfony\Component\AssetMapper\ImportMap\ImportMapManager;
use Symfony\Component\AssetMapper\ImportMap\TraceableImportMapRenderer;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class ImportMapDataCollector extends DataCollector
{
    public function __construct(
        private TraceableImportMapRenderer $importMapRenderer,
        private ImportMapManager $importMapManager,
    )
    {
    }

    public function getName(): string
    {
        return 'importmap';
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $this->data['import_map_rendered'] = $this->importMapRenderer->wasImportMapRendered();
        if (!$this->data['import_map_rendered']) {
            return;
        }

        $this->data['entrypoints'] = $this->importMapRenderer->getEntryPointNames();
        $this->data['entrypoint_assets'] = $this->collectEntrypointAssets($this->data['entrypoints']);

        $this->data['raw_importmap_data'] = $this->importMapManager->getRawImportMapData();
        $this->data['final_importmap_data'] = $this->importMapManager->getImportMapData($this->data['entrypoints']);
        dump($this->data);
    }

    public function wasImportMapRendered(): bool
    {
        return $this->data['import_map_rendered'];
    }

    public function getEntryPoints(): array
    {
        return $this->data['entrypoints'];
    }

    /**
     * @return MappedAsset[]
     */
    public function getEntryPointNames(): array
    {
        $entrypoints = [];
        foreach ($this->data['entrypoints'] as $entrypoint) {
            $asset = $this->data['entrypoint_assets'][$entrypoint];
            if (!$asset) {
                $entrypoints[] = $entrypoint;
            }

            $entrypoints[] = basename($asset->logicalPath);
        }

        return $entrypoints;
    }

    public function getCssLinkTags(): array
    {
        $linkTags = [];
        foreach ($this->data['final_importmap_data'] as $importName => $data) {
            if ('css' === $data['type'] && ($data['preload'] ?? false)) {
                $linkTags[$importName] = $data['path'];
            }
        }

        return $linkTags;
    }

    public function getPreloadedScripts(): array
    {
        $preloadedScripts = [];
        foreach ($this->data['final_importmap_data'] as $importName => $data) {
            if ('js' === $data['type'] && ($data['preload'] ?? false)) {
                $preloadedScripts[$importName] = $data['path'];
            }
        }

        return $preloadedScripts;
    }

    private function findAssetFromEntrypoint(string $entrypointName): ?MappedAsset
    {
        $entry = $this->importMapManager->findRootImportMapEntry($entrypointName);
        if (!$entry) {
            return null;
        }

        if (null === $entry->path) {
            return null;
        }

        return $this->importMapManager->findAsset($entry->path);
    }

    private function collectEntrypointAssets(array $entrypoints): array
    {
        $assets = [];
        foreach ($entrypoints as $entrypoint) {
            $asset = $this->findAssetFromEntrypoint($entrypoint);
            $assets[$entrypoint] = $asset;
        }

        return $assets;
    }
}
