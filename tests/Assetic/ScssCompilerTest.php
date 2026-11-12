<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use October\Rain\Assetic\Asset\FileAsset;
use October\Rain\Assetic\Factory\AssetFactory;
use October\Rain\Assetic\Filter\ScssCompiler;
use October\Rain\Events\Dispatcher;
use October\Rain\Events\PriorityDispatcher;
use ScssPhp\ScssPhp\Node\Number;
use ScssPhp\ScssPhp\OutputStyle;
use ScssPhp\ScssPhp\Value\SassNumber;
use ScssPhp\ScssPhp\Value\Value;

class ScssCompilerTest extends TestCase
{
    protected $facadeApplication;

    protected $fixturePath;

    public function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = realpath(__DIR__.'/../fixtures/assetic/scss');

        // The SCSS compiler registers an event listener when constructed and reads config when compiling
        $this->facadeApplication = Facade::getFacadeApplication();
        $app = new Container;
        $app->instance('config', new Repository(['cms' => ['enable_asset_source_maps' => false]]));
        $app->instance('events', new Dispatcher($app));
        $app->instance('events.priority', (new PriorityDispatcher($app))->setLaravelDispatcher($app['events']));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }

    public function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->facadeApplication);

        parent::tearDown();
    }

    public function testCompileResolvesImportsFromTheSourceDirectory()
    {
        $css = $this->compile(new ScssCompiler, file_get_contents($this->fixturePath.'/theme.scss'));

        $this->assertStringContainsString('@import "plain.css";', $css);
        $this->assertStringContainsString('.component {', $css);
        $this->assertStringContainsString('color: #112233;', $css);
        $this->assertStringContainsString('border-radius: 4px;', $css);
    }

    public function testPresetVariablesCompileWithoutDeprecations()
    {
        $filter = new ScssCompiler;
        $filter->setPresets([
            'link-color' => '#ff0000',
            'font-stack' => 'Helvetica, sans-serif',
            'gutter' => 12,
            'is-dark' => true,
            'unset-value' => null,
            'empty-value' => '',
            'broken-value' => 'foo; bar',
        ]);

        $deprecations = [];
        set_error_handler(function ($level, $message) use (&$deprecations) {
            $deprecations[] = $message;
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);

        try {
            $css = $this->compile($filter, implode("\n", [
                'a {',
                '    color: $link-color;',
                '    font-family: $font-stack;',
                '    padding: $gutter * 1px;',
                '    content: "#{$empty-value}";',
                '    border: $unset-value;',
                '    quotes: $broken-value;',
                '    @if $is-dark { background: black; }',
                '}',
            ]));
        }
        finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
        $this->assertStringContainsString('color: #ff0000;', $css);
        $this->assertStringContainsString('font-family: Helvetica, sans-serif;', $css);
        $this->assertStringContainsString('padding: 12px;', $css);
        $this->assertStringContainsString('content: "";', $css);
        $this->assertStringContainsString('quotes: foo; bar;', $css);
        $this->assertStringContainsString('background: black;', $css);
        $this->assertStringNotContainsString('border:', $css);
    }

    public function testOutputStyleAcceptsStringsAndEnums()
    {
        $filter = new ScssCompiler;
        $filter->setFormatter('compressed');
        $this->assertSame('a{color:red}', trim($this->compile($filter, 'a { color: red; }')));

        $filter->setFormatter(OutputStyle::EXPANDED);
        $this->assertStringContainsString("a {\n  color: red;\n}", $this->compile($filter, 'a { color: red; }'));
    }

    public function testRegisterFunctionWithoutDeclarationReceivesPositionalArguments()
    {
        $filter = new ScssCompiler;
        $filter->registerFunction('add-all', function (array $args) {
            $total = 0;
            foreach ($args as $arg) {
                $total += $arg->getDimension();
            }

            return new Number($total, '');
        });

        $this->assertStringContainsString('width: 6;', $this->compile($filter, 'a { width: add-all(1, 2, 3); }'));
    }

    public function testRegisterFunctionWithDeclarationUsesValueObjects()
    {
        $filter = new ScssCompiler;
        $filter->registerFunction('double', function (array $args): Value {
            return SassNumber::create($args[0]->assertNumber()->getValue() * 2, 'px');
        }, ['number']);

        $this->assertStringContainsString('width: 8px;', $this->compile($filter, 'a { width: double(4); }'));
    }

    public function testGetChildrenResolvesPartialsDirectoriesAndNestedImports()
    {
        $filter = new ScssCompiler;
        $factory = new AssetFactory($this->fixturePath);

        $children = $filter->getChildren(
            $factory,
            file_get_contents($this->fixturePath.'/theme.scss'),
            $this->fixturePath
        );

        $paths = array_map(function ($child) {
            return str_replace('\\', '/', $child->getSourceRoot().'/'.$child->getSourcePath());
        }, $children);

        $expected = array_map(function ($path) {
            return str_replace('\\', '/', $this->fixturePath).'/'.$path;
        }, [
            '_mixins.scss',
            'partials/_colors.scss',
            'partials/_shades.scss',
            'components/_index.scss',
        ]);

        $this->assertSame($expected, $paths);
    }

    public function testHashAssetChangesWhenANestedPartialChanges()
    {
        $filter = new ScssCompiler;
        $theme = $this->fixturePath.'/theme.scss';
        $shades = $this->fixturePath.'/partials/_shades.scss';
        $originalTime = filemtime($shades);

        try {
            $before = $filter->hashAsset($theme, $this->fixturePath);
            touch($shades, $originalTime + 10);
            clearstatcache();
            $after = $filter->hashAsset($theme, $this->fixturePath);
        }
        finally {
            touch($shades, $originalTime);
            clearstatcache();
        }

        $this->assertNotSame($before, $after);
    }

    /**
     * compile runs SCSS through the filter as if it were loaded from the fixture directory.
     */
    protected function compile(ScssCompiler $filter, string $scss): string
    {
        $asset = new FileAsset($this->fixturePath.'/theme.scss');
        $asset->setContent($scss);

        $filter->filterLoad($asset);

        return $asset->getContent();
    }
}
