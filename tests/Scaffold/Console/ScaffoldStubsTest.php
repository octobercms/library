<?php

use October\Rain\Scaffold\Console\CreatePlugin;
use October\Rain\Scaffold\Console\CreateController;
use October\Rain\Scaffold\Console\CreateFormWidget;
use October\Rain\Scaffold\Console\CreateFilterWidget;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * ScaffoldStubsTest renders the scaffold stubs the way the create commands do
 */
class ScaffoldStubsTest extends TestCase
{
    /**
     * @var mixed savedFacadeApp
     */
    protected $savedFacadeApp;

    /**
     * setUp test
     */
    public function setUp(): void
    {
        // Wire a real container as the facade root so the Twig facade resolves
        $this->savedFacadeApp = Illuminate\Support\Facades\Facade::getFacadeApplication();

        $app = new Illuminate\Container\Container;
        $app->singleton('parse.twig', fn () => new October\Rain\Parse\Twig);
        $app->singleton('twig.environment', fn () => new Twig\Environment(new Twig\Loader\ArrayLoader));
        $app->instance('app', $app);
        Illuminate\Support\Facades\Facade::clearResolvedInstances();
        Illuminate\Support\Facades\Facade::setFacadeApplication($app);
    }

    /**
     * tearDown test
     */
    public function tearDown(): void
    {
        Illuminate\Support\Facades\Facade::setFacadeApplication($this->savedFacadeApp);
    }

    /**
     * testPluginStubUsesPhosphorIcons confirms the plugin registration file uses Phosphor icons
     */
    public function testPluginStubUsesPhosphorIcons()
    {
        $files = $this->renderStubs(new ScaffoldStubsTestCreatePlugin, ['namespace' => 'Acme.Blog']);

        $this->assertValidPhp($files);
        $this->assertNoLegacyIcons($files);
        $this->assertSame(2, substr_count($files['Plugin.php'], "'icon' => 'ph ph-leaf'"));
    }

    /**
     * testControllerStubsUseUiHelpers confirms each controller design renders its buttons with Ui helpers and Phosphor icons
     */
    public function testControllerStubsUseUiHelpers()
    {
        foreach (['', 'popup', 'basic'] as $design) {
            $files = $this->renderStubs(new ScaffoldStubsTestCreateController, [
                'namespace' => 'Acme.Blog',
                'name' => 'Posts',
                '--design' => $design,
            ]);

            $this->assertValidPhp($files);
            $this->assertNoLegacyIcons($files);
            $this->assertNoHandBuiltButtons($files);
            $this->assertStringContainsString("icon: 'ph ph-plus'", $files['controllers/posts/_list_toolbar.php']);
            $this->assertStringContainsString("icon: 'ph ph-trash'", $files['controllers/posts/_list_toolbar.php']);
        }

        $files = $this->renderStubs(new ScaffoldStubsTestCreateController, ['namespace' => 'Acme.Blog', 'name' => 'Posts']);

        foreach (['create.php', 'update.php', 'preview.php'] as $view) {
            $this->assertStringContainsString('Ui::', $files["controllers/posts/{$view}"]);
        }

        $this->assertStringContainsString("icon: 'ph ph-trash'", $files['controllers/posts/update.php']);
        $this->assertStringContainsString("formCheckPermission('modelDelete')", $files['controllers/posts/update.php']);
        $this->assertStringContainsString("Ui::popupButton(", $this->renderStubs(new ScaffoldStubsTestCreateController, [
            'namespace' => 'Acme.Blog',
            'name' => 'Posts',
            '--design' => 'popup',
        ])['controllers/posts/_list_toolbar.php']);
    }

    /**
     * testWidgetStubsUseUiHelpersAndEscapeValues confirms the filter and form widget partials use Ui helpers, translate strings and escape values
     */
    public function testWidgetStubsUseUiHelpersAndEscapeValues()
    {
        $filterFiles = $this->renderStubs(new ScaffoldStubsTestCreateFilterWidget, ['namespace' => 'Acme.Blog', 'name' => 'HasDiscount']);

        $this->assertValidPhp($filterFiles);
        $this->assertNoHandBuiltButtons($filterFiles);
        $this->assertStringContainsString("dataFilterAction: 'apply'", $filterFiles['filterwidgets/hasdiscount/partials/_hasdiscount_form.php']);
        $this->assertStringContainsString('e(__($scope->label))', $filterFiles['filterwidgets/hasdiscount/partials/_hasdiscount.php']);

        $formFiles = $this->renderStubs(new ScaffoldStubsTestCreateFormWidget, ['namespace' => 'Acme.Blog', 'name' => 'PostList']);
        $partial = $formFiles['formwidgets/postlist/partials/_postlist.php'];

        $this->assertValidPhp($formFiles);
        $this->assertStringNotContainsString('<?= $value ?>', $partial);
        $this->assertSame(2, substr_count($partial, 'e($value)'));
    }

    /**
     * renderStubs returns the rendered stubs of a create command keyed by output path
     */
    protected function renderStubs(October\Rain\Scaffold\GeneratorCommandBase $command, array $input): array
    {
        $command->setInput(new ArrayInput($input, $command->getDefinition()));

        return $command->renderStubs();
    }

    /**
     * assertValidPhp parses each rendered PHP file to catch syntax errors
     */
    protected function assertValidPhp(array $files): void
    {
        foreach ($files as $name => $contents) {
            if (substr($name, -4) !== '.php') {
                continue;
            }

            try {
                token_get_all($contents, TOKEN_PARSE);
            }
            catch (ParseError $ex) {
                $this->fail(sprintf('Rendered %s is not valid PHP: %s', $name, $ex->getMessage()));
            }
        }
    }

    /**
     * assertNoLegacyIcons checks the rendered files do not use octo-icon classes
     */
    protected function assertNoLegacyIcons(array $files): void
    {
        foreach ($files as $name => $contents) {
            preg_match_all('/(?<![\w-])(?:oc-)?icon-[\w-]+/', $contents, $matches);

            $this->assertSame([], $matches[0], "Legacy icon classes found in {$name}");
        }
    }

    /**
     * assertNoHandBuiltButtons checks the rendered PHP views leave their buttons to the Ui helpers
     */
    protected function assertNoHandBuiltButtons(array $files): void
    {
        foreach ($files as $name => $contents) {
            if (substr($name, -4) !== '.php') {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression('/<button\b/', $contents, "Hand-built button found in {$name}");
            $this->assertDoesNotMatchRegularExpression('/class="[^"]*(?<![\w-])btn(?![\w-])/', $contents, "Hand-built btn class found in {$name}");
        }
    }
}

/**
 * ScaffoldStubsTestRendering collects rendered stubs in memory instead of writing files
 */
trait ScaffoldStubsTestRendering
{
    /**
     * @var array rendered contents keyed by output path
     */
    protected $rendered = [];

    /**
     * renderStubs prepares the variables and renders every stub the command makes
     */
    public function renderStubs(): array
    {
        $this->rendered = [];

        $this->vars = $this->processVars($this->prepareVars());

        $this->makeStubs();

        return $this->rendered;
    }

    /**
     * makeStub renders a single stub with the command variables
     */
    public function makeStub(string $stubName, string $outputName)
    {
        $contents = $this->files->get($this->getSourcePath() . '/' . $stubName);

        $this->rendered[Twig::parse($outputName, $this->vars)] = Twig::parse($contents, $this->vars);
    }

    /**
     * getSourcePath returns the stub directory of the create command being tested
     */
    protected function getSourcePath(): string
    {
        return dirname((new ReflectionClass(get_parent_class($this)))->getFileName());
    }
}

/**
 * ScaffoldStubsTestCreatePlugin renders the create:plugin stubs
 */
class ScaffoldStubsTestCreatePlugin extends CreatePlugin
{
    use ScaffoldStubsTestRendering;
}

/**
 * ScaffoldStubsTestCreateController renders the create:controller stubs
 */
class ScaffoldStubsTestCreateController extends CreateController
{
    use ScaffoldStubsTestRendering;
}

/**
 * ScaffoldStubsTestCreateFilterWidget renders the create:filterwidget stubs
 */
class ScaffoldStubsTestCreateFilterWidget extends CreateFilterWidget
{
    use ScaffoldStubsTestRendering;
}

/**
 * ScaffoldStubsTestCreateFormWidget renders the create:formwidget stubs
 */
class ScaffoldStubsTestCreateFormWidget extends CreateFormWidget
{
    use ScaffoldStubsTestRendering;
}
