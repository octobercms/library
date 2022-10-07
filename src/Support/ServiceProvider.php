<?php namespace October\Rain\Support;

use Illuminate\Support\ServiceProvider as ServiceProviderBase;

/**
 * ServiceProvider is an empty umbrella class
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ServiceProvider extends ServiceProviderBase
{
    /**
     * @var \October\Rain\Foundation\Application app instance
     */
    protected $app;
}
