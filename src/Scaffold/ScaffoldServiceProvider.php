<?php namespace October\Rain\Scaffold;

use Illuminate\Support\ServiceProvider;
use October\Rain\Scaffold\Console\CreateModel;
use October\Rain\Scaffold\Console\CreatePlugin;
use October\Rain\Scaffold\Console\CreateCommand;
use October\Rain\Scaffold\Console\CreateComponent;
use October\Rain\Scaffold\Console\CreateController;
use October\Rain\Scaffold\Console\CreateFormWidget;
use Illuminate\Contracts\Support\DeferrableProvider;

class ScaffoldServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        'command.create.plugin' => CreatePlugin::class,
        'command.create.model' => CreateModel::class,
        'command.create.controller' => CreateController::class,
        'command.create.component' => CreateComponent::class,
        'command.create.formwidget' => CreateFormWidget::class,
        'command.create.command' => CreateCommand::class,
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    'command.create.plugin',
                    'command.create.model',
                    'command.create.controller',
                    'command.create.component',
                    'command.create.formwidget',
                    'command.create.command',
                ]
            );
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.create.plugin',
            'command.create.model',
            'command.create.controller',
            'command.create.component',
            'command.create.formwidget',
            'command.create.command',
        ];
    }
}
