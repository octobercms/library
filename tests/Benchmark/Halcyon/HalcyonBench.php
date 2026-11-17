<?php

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use October\Rain\Filesystem\Filesystem;
use October\Rain\Halcyon\Datasource\FileDatasource;
use October\Rain\Halcyon\Datasource\Resolver;
use October\Rain\Halcyon\Model;

require_once __DIR__.'/../../fixtures/halcyon/models/Page.php';

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 * @Iterations(5)
 */
class HalcyonBench
{
    /**
     * init
     */
    public function init()
    {
        $app = new Container;
        $app->instance('config', new Repository([
            'cache' => [
                'default' => 'array',
                'stores' => ['array' => ['driver' => 'array']],
            ],
        ]));
        Model::setCacheManager(new CacheManager($app));

        $theme = new FileDatasource(realpath(__DIR__.'/../../fixtures/halcyon/themes/theme1'), new Filesystem);
        Model::setDatasourceResolver(new Resolver(['theme1' => $theme]));

        $page = HalcyonTestPage::on('theme1')->remember(60)->find('home');
        $cached = HalcyonTestPage::on('theme1')->remember(60)->find('home');
        if (!$page || !$cached->isLoadedFromCache() || $cached->title !== $page->title) {
            throw new RuntimeException('Halcyon fixture mismatch');
        }
    }

    /**
     * @Subject
     */
    public function benchFind()
    {
        HalcyonTestPage::on('theme1')->find('home');
    }

    /**
     * @Subject
     */
    public function benchFindCached()
    {
        HalcyonTestPage::on('theme1')->remember(60)->find('home');
    }
}
