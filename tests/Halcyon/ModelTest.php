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

        $this->setThemeResolver();

        $this->setValidatorOnModel();
    }

    public function testFindAll()
    {
        $pages = HalcyonTestPage::all();

        $this->assertCount(2, $pages);
        $this->assertContains('about.htm', $pages->lists('fileName'));
        $this->assertContains('home.htm', $pages->lists('fileName'));
    }

    public function testFindPage()
    {
        $page = HalcyonTestPage::find('home');
        $this->assertNotNull($page);
        $this->assertCount(6, $page->attributes);
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
        $this->assertCount(6, $page->attributes);
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
            'viewBag' => ['foo' => 'bar'],
            'markup' => '<p>Hello world!</p>',
            'code' => 'function onStart() { }'
        ]);

        $targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile.htm';

        $this->assertFileExists($targetFile);

        $content = <<<ESC
title = "Test page"

[viewBag]
foo = "bar"
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

    public function testCreatePageInDirectoryPass()
    {
        HalcyonTestPage::create([
            'fileName' => 'walking/on-sunshine.htm',
            'title' => 'Katrina & The Waves',
            'markup' => '<p>Woo!</p>',
        ]);

        $targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/walking/on-sunshine.htm';

        $this->assertFileExists($targetFile);

        @unlink($targetFile);
        @rmdir(dirname($targetFile));
    }

    /**
     * @expectedException        October\Rain\Halcyon\Exception\InvalidFileNameException
     * @expectedExceptionMessage The specified file name [one/small/step/for-man.htm] is invalid.
     */
    public function testCreatePageInDirectoryFail()
    {
        HalcyonTestPage::create([
            'fileName' => 'one/small/step/for-man.htm',
            'title' => 'One Giant Leap',
            'markup' => '<p>For man-kind</p>',
        ]);
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

    /**
     * @expectedException        October\Rain\Halcyon\Exception\FileExistsException
     * @expectedExceptionMessage A file already exists
     */
    public function testUpdatePageFileExists()
    {
        $page = HalcyonTestPage::create([
            'fileName' => 'testfile2a',
            'title' => 'Another test',
            'markup' => '<p>Foo bar!</p>'
        ]);

        $targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile2a.htm';

        $this->assertFileExists($targetFile);
        $this->assertEquals('Another test', $page->title);

        $page = HalcyonTestPage::find('testfile2a');
        $page->fileName = 'about';

        @unlink($targetFile);

        $page->save();
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

    /**
     * @expectedException        October\Rain\Halcyon\Exception\ModelException
     * @expectedExceptionMessage The title field is required.
     */
    public function testPageWithValidation()
    {
        $page = new HalcyonTestPageWithValidation;
        $page->fileName = 'with-validation';
        $page->save();

        $page->delete();
    }

    /**
     * @expectedException        October\Rain\Halcyon\Exception\ModelException
     * @expectedExceptionMessage The meta title field is required.
     */
    public function testPageWithNestedValidationFail()
    {
        $page = new HalcyonTestPageWithValidation;
        $page->fileName = 'with-validation';
        $page->title = "Pass";
        $page->save();

        $page->delete();
    }

    public function testPageWithNestedValidationPass()
    {
        $page = new HalcyonTestPageWithValidation;
        $page->fileName = 'with-validation';
        $page->title = "Pass";
        $page->viewBag = ['meta_title' => 'Oh yeah'];
        $page->save();

        $page->delete();
    }

    //
    // House keeping
    //

    protected function setThemeResolver()
    {
        $theme1 = new FileTheme(__DIR__.'/../fixtures/halcyon/themes/theme1', new Filesystem);
        $this->resolver = new ThemeResolver(['theme1' => $theme1]);
        $this->resolver->setDefaultTheme('theme1');

        $theme2 = new FileTheme(__DIR__.'/../fixtures/halcyon/themes/theme2', new Filesystem);
        $this->resolver->addTheme('theme2', $theme2);

        Model::setThemeResolver($this->resolver);
    }

    protected function setValidatorOnModel()
    {
        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')->setMethods([
            'get',
            'trans',
            'transChoice',
            'setLocale',
            'getLocale'
        ])->getMock();

        $translator->expects($this->any())->method('get')->will($this->returnArgument(0));

        $factory = new \Illuminate\Validation\Factory($translator);

        HalcyonTestPageWithValidation::setModelValidator($factory);
    }
}
