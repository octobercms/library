<?php

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use October\Rain\Assetic\Combiner;
use October\Rain\Assetic\Filter\StylesheetMinify;
use October\Rain\Events\Dispatcher;
use October\Rain\Events\PriorityDispatcher;

class CombinerTest extends TestCase
{
    protected $facadeApplication;

    public function setUp(): void
    {
        parent::setUp();

        // The SCSS compiler registers an event listener when constructed
        $this->facadeApplication = Facade::getFacadeApplication();
        $app = new Container;
        $app->instance('events', new Dispatcher($app));
        $app->instance('events.priority', (new PriorityDispatcher($app))->setLaravelDispatcher($app['events']));
        Facade::clearResolvedInstance('events.priority');
        Facade::setFacadeApplication($app);
    }

    public function tearDown(): void
    {
        Facade::clearResolvedInstance('events.priority');
        Facade::setFacadeApplication($this->facadeApplication);

        parent::tearDown();
    }

    public function testRegisterProductionFilterForManyExtensions()
    {
        $combiner = new Combiner;
        $filter = new StylesheetMinify;

        $result = $combiner->registerFilter(['css', 'less'], $filter, true);

        $this->assertSame($combiner, $result);
        $this->assertNotContains($filter, $combiner->getFilters('css'));
        $this->assertNotContains($filter, $combiner->getFilters('less'));
        $this->assertContains($filter, $combiner->getFilters('css', true));
        $this->assertContains($filter, $combiner->getFilters('less', true));
    }

    public function testDefaultMinifyFiltersAreProductionOnly()
    {
        $combiner = new Combiner;
        $combiner->registerDefaultFilters();

        foreach (['css', 'less', 'scss'] as $extension) {
            $filters = array_map('get_class', $combiner->getFilters($extension));
            $prodFilters = array_map('get_class', $combiner->getFilters($extension, true));

            $this->assertNotContains(StylesheetMinify::class, $filters);
            $this->assertContains(StylesheetMinify::class, $prodFilters);
        }
    }
}
