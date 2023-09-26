<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\AssetMapper\DataCollector\ImportMapDataCollector;
use Symfony\Component\AssetMapper\ImportMap\TraceableImportMapRenderer;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('debug.asset_mapper.importmap.renderer', TraceableImportMapRenderer::class)
            ->decorate('asset_mapper.importmap.renderer')
            ->args([
                service('debug.asset_mapper.importmap.renderer.inner'),
            ])

        ->set('asset_mapper.data_collector', ImportMapDataCollector::class)
            ->args([
                service('debug.asset_mapper.importmap.renderer'),
                service('asset_mapper.importmap.manager'),
            ])
            ->tag('data_collector', [
                'template' => '@WebProfiler/Collector/importmap.html.twig',
                'id' => 'importmap',
            ])
    ;
};
