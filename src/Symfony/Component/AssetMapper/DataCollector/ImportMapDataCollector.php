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
        if (!$this->importMapRenderer->wasImportMapRendered()) {
            $this->data = [
                'import_map_rendered' => false,
            ];
            return;
        }

        $entrypoints = $this->importMapRenderer->getEntryPointNames();

        $this->data = [
            'import_map_rendered' => true,
            'entrypoints' => $entrypoints,
            'entrypoint_assets' => $this->collectEntrypointAssets($entrypoints),
            'raw_importmap_data' => [],
            'final_importmap_data' => [],
        ];

        return;

        //$this->data['raw_importmap_data'] = $this->importMapManager->getRawImportMapData();
        //$this->data['final_importmap_data'] = $this->importMapManager->getImportMapData($this->data['entrypoints']);

        $this->data =array_map($this->getDependencies(...), $this->data['entrypoint_assets']);
    }

    public function wasImportMapRendered(): bool
    {
        return $this->data['import_map_rendered'] ?? false;
    }

    /**
     * @return array<string, array{path: string, type: string, preload?: bool}>
     * @internal
     */
    public function getFinalImportMapData() {
        return $this->data['final_importmap_data'] ?? [];
    }

    /**
     * @return array<string, array{path: string, type: string}>
     * @internal
     */
    public function getRawImportMapData(): array
    {
        return $this->data['raw_importmap_data'] ?? [];
    }

    /**
     * @return string[]
     */
    public function getEntryPoints(): array
    {
        return $this->data['entrypoints'] ?? [];
    }

    /**
     * @return MappedAsset[]
     */
    public function getEntryPointAssets(): array
    {
        return $this->data['entrypoint_assets'] ?? [];
    }

    /**
     */
    public function getTree(): array
    {
        return $this->data['tree'];
    }

    public function getDependencies(MappedAsset $asset): array
    {
        $dependencies = [];

        foreach ($asset->getJavaScriptImports() as $import) {
            if ($import->isLazy) {
                $dependencies[$import->importName]['loading'] = 'lazy';
                continue;
            }

            $dependencies[$import->importName]['loading'] = 'eager';

            if ($import->asset instanceof MappedAsset) {
                $dependencies[$import->importName]['imports'] = $this->getDependencies($import->asset);
            }
        }

        return $dependencies;
    }

    /**
     * @return string[]
     */
    public function getEntryPointNames(): array
    {
        $entrypoints = [];
        foreach ($this->data['entrypoints'] as $entrypoint) {
            $asset = $this->data['entrypoint_assets'][$entrypoint];
            if (!$asset) {
                $entrypoints[] = $entrypoint;
                continue;
            }

            $entrypoints[] = basename($asset->logicalPath);
        }

        return $entrypoints;
    }

    /**
     * @return array<string, string>
     */
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

    /**
     * @return array<string, string>
     */
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
