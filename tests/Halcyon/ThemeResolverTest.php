<?php

use October\Rain\Filesystem\Filesystem;
use October\Rain\Halcyon\Theme\FileTheme;
use October\Rain\Halcyon\Theme\ThemeResolver;

class ThemeResolverTest extends TestCase
{

    public function testConstruct()
    {
        $theme1 = new FileTheme('themes/', 'theme1', new Filesystem);
        $theme2 = new FileTheme('themes/', 'theme2', new Filesystem);
        $theme3 = new FileTheme('themes/', 'theme3', new Filesystem);

        $resolver = new ThemeResolver([
            'theme1' => $theme1,
            'theme2' => $theme2,
            'theme3' => $theme3
        ]);

        $this->assertTrue($resolver->hasTheme('theme1'));
        $this->assertTrue($resolver->hasTheme('theme2'));
        $this->assertTrue($resolver->hasTheme('theme3'));
        $this->assertFalse($resolver->hasTheme('theme4'));
    }

    public function testDefaultTheme()
    {
        $resolver = new ThemeResolver;
        $resolver->setDefaultTheme('theme1');
        $this->assertEquals('theme1', $resolver->getDefaultTheme());
    }

}
