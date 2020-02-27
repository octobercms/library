<?php

use October\Rain\Filesystem\Filesystem;
use October\Rain\Halcyon\Datasource\Resolver;
use October\Rain\Halcyon\Datasource\FileDatasource;

class DatasourceResolverTest extends TestCase
{

    public function testConstruct()
    {
        $theme1 = new FileDatasource('themes/theme1', new Filesystem);
        $theme2 = new FileDatasource('themes/theme2', new Filesystem);
        $theme3 = new FileDatasource('themes/theme3', new Filesystem);

        $resolver = new Resolver([
            'theme1' => $theme1,
            'theme2' => $theme2,
            'theme3' => $theme3
        ]);

        $this->assertTrue($resolver->hasDatasource('theme1'));
        $this->assertTrue($resolver->hasDatasource('theme2'));
        $this->assertTrue($resolver->hasDatasource('theme3'));
        $this->assertFalse($resolver->hasDatasource('theme4'));
    }

    public function testDefaultDatasource()
    {
        $resolver = new Resolver;
        $resolver->setDefaultDatasource('theme1');
        $this->assertEquals('theme1', $resolver->getDefaultDatasource());
    }
}
