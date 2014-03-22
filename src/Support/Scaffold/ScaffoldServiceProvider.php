<?php namespace October\Rain\Support\Scaffold;

use Illuminate\Support\ServiceProvider;
use October\Rain\Support\Scaffold\Console\CreatePlugin;
use October\Rain\Support\Scaffold\Console\CreateModel;
use October\Rain\Support\Scaffold\Console\CreateController;
use October\Rain\Support\Scaffold\Console\CreateComponent;

class ScaffoldServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('command.create.plugin', function() {
            return new CreatePlugin;
        });

        $this->app->bindShared('command.create.model', function() {
            return new CreateModel;
        });

        $this->app->bindShared('command.create.controller', function() {
            return new CreateController;
        });

        $this->app->bindShared('command.create.component', function() {
            return new CreateComponent;
        });

        $this->commands('command.create.plugin');
        $this->commands('command.create.model');
        $this->commands('command.create.controller');
        $this->commands('command.create.component');
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return [
            'command.create.plugin',
            'command.create.model',
            'command.create.controller',
            'command.create.component',
        ];
    }
}