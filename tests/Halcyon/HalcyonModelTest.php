<?php

use October\Rain\Halcyon\Model;
use October\Rain\Halcyon\Datasource\Resolver;
use October\Rain\Halcyon\Datasource\FileDatasource;
use October\Rain\Filesystem\Filesystem;

class HalcyonModelTest extends TestCase
{
    protected $resolver;

    public function setUp(): void
    {
        include_once __DIR__.'/../fixtures/halcyon/models/Page.php';
        include_once __DIR__.'/../fixtures/halcyon/models/Menu.php';
        include_once __DIR__.'/../fixtures/halcyon/models/Content.php';

        $this->setDatasourceResolver();

        $this->setValidatorOnModel();
    }

    public function testFindAll()
    {
        $pages = HalcyonTestPage::all();

        $this->assertCount(4, $pages);
        $this->assertContains('about.htm', $pages->lists('fileName'));
        $this->assertContains('home.htm', $pages->lists('fileName'));
        $this->assertContains('level1/team.htm', $pages->lists('fileName'));
        $this->assertContains('level1/level2/level3/level4/level5/contact.htm', $pages->lists('fileName'));
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

    public function testFindMenu()
    {
        $menu = HalcyonTestMenu::find('mainmenu');
        $this->assertNotNull($menu);
        $this->assertEquals('<ul><li>Home</li></ul>', $menu->content);
    }

    public function testOtherDatasourcePage()
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
        @unlink($targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile.htm');

        HalcyonTestPage::create([
            'fileName' => 'testfile.htm',
            'title' => 'Test page',
            'viewBag' => ['foo' => 'bar'],
            'markup' => '<p>Hello world!</p>',
            'code' => 'function onStart() { }'
        ]);

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

        $expected = file_get_contents($targetFile);
        $expected = preg_replace('~\R~u', PHP_EOL, $expected); // Normalize EOL
        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL
        $this->assertEquals($content, $expected);

        @unlink($targetFile);
    }

    public function testCreateMenu()
    {
        @unlink($targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/menus/testfile.htm');

        HalcyonTestMenu::create([
            'fileName' => 'testfile',
            'content' => '<p>Hello world!</p>'
        ]);


        $this->assertFileExists($targetFile);

        $content = <<<ESC
<p>Hello world!</p>
ESC;

        $this->assertEquals($content, file_get_contents($targetFile));

        @unlink($targetFile);
    }

    public function testCreatePageInDirectoryPass()
    {
        @unlink($targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/walking/on-sunshine.htm');

        HalcyonTestPage::create([
            'fileName' => 'walking/on-sunshine.htm',
            'title' => 'Katrina & The Waves',
            'markup' => '<p>Woo!</p>',
        ]);

        $this->assertFileExists($targetFile);

        @unlink($targetFile);
        @rmdir(dirname($targetFile));
    }

    public function testCreatePageInDirectoryFail()
    {
        $this->expectException(\October\Rain\Halcyon\Exception\InvalidFileNameException::class);
        $this->expectExceptionMessage('The specified file name [one/small/step/for-man.htm] is invalid.');

        HalcyonTestPage::create([
            'fileName' => 'one/small/step/for-man.htm',
            'title' => 'One Giant Leap',
            'markup' => '<p>For man-kind</p>',
        ]);
    }

    public function testUpdatePage()
    {
        @unlink($targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile2.htm');

        $page = HalcyonTestPage::create([
            'fileName' => 'testfile2',
            'title' => 'Another test',
            'markup' => '<p>Foo bar!</p>'
        ]);

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
    }

    public function testUpdatePageRenameFile()
    {
        @unlink($targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile2.htm');

        $page = HalcyonTestPage::create([
            'fileName' => 'testfile2',
            'title' => 'Another test',
            'markup' => '<p>Foo bar!</p>'
        ]);

        $this->assertFileExists($targetFile);

        $page->fileName = 'renamedtest1';
        $page->save();

        $newTargetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/renamedtest1.htm';
        $this->assertFileNotExists($targetFile);
        $this->assertFileExists($newTargetFile);

        @unlink($newTargetFile);
    }

    public function testUpdatePageRenameFileCase()
    {
        @unlink($targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/Test.htm');

        $page = HalcyonTestPage::create([
            'fileName' => 'Test',
            'title' => 'Upper case file',
            'markup' => '<p>I have an upper case, it should be lower</p>'
        ]);

        $this->assertFileExists($targetFile);

        $page->fileName = 'test';
        $page->save();

        $newTargetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/test.htm';
        $this->assertFileExists($newTargetFile);

        @unlink($newTargetFile);
    }

    public function testUpdateContentRenameExtension()
    {
        $content = HalcyonTestContent::find('welcome.htm');
        $this->assertNotNull($content);
        $this->assertCount(5, $content->attributes);
        $this->assertArrayHasKey('fileName', $content->attributes);
        $this->assertEquals('welcome.htm', $content->fileName);
        $this->assertEquals('<p>Hi friend</p>', $content->markup);

        $targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/content/welcome.htm';
        $newTargetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/content/welcome.txt';

        $this->assertFileExists($targetFile);

        $content->fileName = 'welcome.txt';
        $content->save();

        $this->assertFileExists($newTargetFile);
        $this->assertFileNotExists($targetFile);

        $content->fileName = 'welcome.htm';
        $content->save();

        $this->assertFileNotExists($newTargetFile);
        $this->assertFileExists($targetFile);
    }

    public function testUpdatePageFileExists()
    {
        $this->expectException(\October\Rain\Halcyon\Exception\FileExistsException::class);
        $this->expectExceptionMessage('A file already exists');

        @unlink($targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile2a.htm');

        $page = HalcyonTestPage::create([
            'fileName' => 'testfile2a',
            'title' => 'Another test',
            'markup' => '<p>Foo bar!</p>'
        ]);

        $this->assertFileExists($targetFile);
        $this->assertEquals('Another test', $page->title);

        $page = HalcyonTestPage::find('testfile2a');
        $page->fileName = 'about';

        @unlink($targetFile);

        $page->save();
    }

    public function testDeletePage()
    {
        @unlink($targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/testfile3.htm');

        $page = HalcyonTestPage::create([
            'fileName' => 'testfile3',
            'title' => 'To be deleted',
        ]);

        $this->assertFileExists($targetFile);

        $page->delete();

        $this->assertFileNotExists($targetFile);
    }

    public function testPageWithValidation()
    {
        $this->expectException(\October\Rain\Halcyon\Exception\ModelException::class);
        $this->expectExceptionMessage('The title field is required.');

        $page = new HalcyonTestPageWithValidation;
        $page->fileName = 'with-validation';
        $page->save();

        $page->delete();
    }

    public function testPageWithNestedValidationFail()
    {
        $this->expectException(\October\Rain\Halcyon\Exception\ModelException::class);
        $this->expectExceptionMessage('The meta title field is required.');

        $page = new HalcyonTestPageWithValidation;
        $page->fileName = 'with-validation';
        $page->title = "Pass";
        $page->save();

        $page->delete();
    }

    public function testPageWithNestedValidationPass()
    {
        $this->expectNotToPerformAssertions();

        $page = new HalcyonTestPageWithValidation;
        $page->fileName = 'with-validation';
        $page->title = "Pass";
        $page->viewBag = ['meta_title' => 'Oh yeah'];
        $page->save();

        $page->delete();
    }

    public function testPageQueryListFileName()
    {
        $page = new HalcyonTestPageWithValidation;
        $files = $page->newQuery()->lists('fileName');
        sort($files);

        $this->assertCount(4, $files);
        $this->assertEquals([
            'about.htm',
            'home.htm',
            'level1/level2/level3/level4/level5/contact.htm',
            'level1/team.htm'
        ], $files);
    }

    public function testAddDynamicProperty()
    {
        @unlink($targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/dynamicproperty.htm');

        try {
            $page = HalcyonTestPage::create([
                'fileName' => 'dynamicproperty',
                'title' => 'Add Dynamic Property',
                'markup' => '<p>Foo bar!</p>'
            ]);

            $page->addDynamicProperty('myDynamicProperty', 'myDynamicPropertyValue');

            // Dynamic property should not hit attributes
            $this->assertArrayNotHasKey('myDynamicProperty', $page->attributes);

            // Should be a real property
            $this->assertTrue(property_exists($page, 'myDynamicProperty'));
            $this->assertEquals('myDynamicPropertyValue', $page->myDynamicProperty);

            $page->save();

            // Should not leak in to attributes
            $page = HalcyonTestPage::find('dynamicproperty');
            $this->assertNotNull($page);
            $this->assertArrayNotHasKey('myDynamicProperty', $page->attributes);
            $this->assertFalse(property_exists($page, 'myDynamicProperty'));
        }
        finally {
            @unlink($targetFile);
        }
    }

    public function testAddBehaviorClass()
    {
        $page = new HalcyonTestPage;
        $page->extendClassWith(HalyconTestExampleBehaviorClass::class);

        $this->assertEquals(null, $page->protectedFoo);
        $this->assertEquals('bar', $page->getFoo());

        // @todo Halycon not advanced enough for this yet
        // $this->assertEquals('foobar', $page->behaviorAttribute);
    }

    //
    // House keeping
    //

    protected function setDatasourceResolver()
    {
        $theme1 = new FileDatasource(realpath(__DIR__.'/../fixtures/halcyon/themes/theme1'), new Filesystem);
        $this->resolver = new Resolver(['theme1' => $theme1]);
        $this->resolver->setDefaultDatasource('theme1');

        $theme2 = new FileDatasource(realpath(__DIR__.'/../fixtures/halcyon/themes/theme2'), new Filesystem);
        $this->resolver->addDatasource('theme2', $theme2);

        Model::setDatasourceResolver($this->resolver);
    }

    protected function setValidatorOnModel()
    {
        $translator = $this->getMockBuilder('Illuminate\Contracts\Translation\Translator')->setMethods([
            'get',
            'choice',
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

class HalyconTestExampleBehaviorClass extends October\Rain\Extension\ExtensionBase
{
    public $behaviorAttribute = 'foobar';

    protected $protectedFoo = 'bar';

    public function getFoo()
    {
        return 'bar';
    }
}
