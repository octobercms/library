<?php

use October\Rain\Halcyon\Model;
use October\Rain\Halcyon\Theme\FileTheme;
use October\Rain\Halcyon\Theme\ThemeResolver;
use October\Rain\Filesystem\Filesystem;

class HalcyonModelTest extends TestCase
{
    protected $resolver;

    public function setUp()
    {
        include_once __DIR__.'/../fixtures/halcyon/models/Page.php';

        $theme1 = new FileTheme(__DIR__.'/../fixtures/halcyon/themes/theme1', new Filesystem);
        $this->resolver = new ThemeResolver(['theme1' => $theme1]);
        $this->resolver->setDefaultTheme('theme1');

        $theme2 = new FileTheme(__DIR__.'/../fixtures/halcyon/themes/theme2', new Filesystem);
        $this->resolver->addTheme('theme2', $theme2);

        Model::setThemeResolver($this->resolver);
    }

    public function testFindAll()
    {
        $pages = HalcyonTestPage::all();

        $this->assertCount(2, $pages);
    }

    public function testFindPage()
    {
        $page = HalcyonTestPage::find('home');
        $this->assertNotNull($page);
        $this->assertCount(5, $page->attributes);
        $this->assertArrayHasKey('fileName', $page->attributes);
        $this->assertEquals('home.htm', $page->fileName);
        $this->assertCount(1, $page->settings);
        $this->assertEquals('<h1>World!</h1>', $page->markup);
        $this->assertEquals('hello', $page->title);
    }

    public function testOtherThemePage()
    {
        $page = HalcyonTestPage::on('theme2')->find('home');
        $this->assertNotNull($page);
        $this->assertCount(5, $page->attributes);
        $this->assertArrayHasKey('fileName', $page->attributes);
        $this->assertEquals('home.htm', $page->fileName);
        $this->assertCount(1, $page->settings);
        $this->assertEquals('<h1>Chisel</h1>', $page->markup);
        $this->assertEquals('Cold', $page->title);
    }

    public function testCreatePage()
    {
        HalcyonTestPage::create([
            'fileName' => 'testfile.htm',
            'title' => 'Test page',
            'markup' => '<p>Hello world!</p>',
            'code' => 'function onStart() { }'
        ]);

        $targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile.htm';

        $this->assertFileExists($targetFile);

        $content = <<<ESC
title = "Test page"
==
<?php
function onStart() { }
?>
==
<p>Hello world!</p>
ESC;

        $this->assertEquals($content, file_get_contents($targetFile));

        @unlink($targetFile);
    }

    public function testUpdatePage()
    {
        $page = HalcyonTestPage::create([
            'fileName' => 'testfile2',
            'title' => 'Another test',
            'markup' => '<p>Foo bar!</p>'
        ]);

        $targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile2.htm';

        $this->assertFileExists($targetFile);
        $this->assertEquals('Another test', $page->title);

        $page = HalcyonTestPage::find('testfile2');
        $this->assertEquals('Another test', $page->title);
        $page->title = 'All done!';
        $page->save();

        $page = HalcyonTestPage::find('testfile2');
        $this->assertEquals('All done!', $page->title);

        $page->update(['title' => 'Try this']);
        $page = HalcyonTestPage::find('testfile2');
        $this->assertEquals('Try this', $page->title);

        $page->fileName = 'renamedtest1';
        $page->save();

        $newTargetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/renamedtest1.htm';
        $this->assertFileNotExists($targetFile);
        $this->assertFileExists($newTargetFile);

        @unlink($newTargetFile);
    }

    public function testDeletePage()
    {
        $page = HalcyonTestPage::create([
            'fileName' => 'testfile3',
            'title' => 'To be deleted',
        ]);

        $targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile3.htm';

        $this->assertFileExists($targetFile);

        $page->delete();

        $this->assertFileNotExists($targetFile);
    }
}
