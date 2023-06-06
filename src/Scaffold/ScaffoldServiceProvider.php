<?php namespace October\Rain\Scaffold;

use October\Rain\Scaffold\Console\CreateCommand;
use October\Rain\Scaffold\Console\CreateFactory;
use October\Rain\Scaffold\Console\CreatePlugin;
use October\Rain\Scaffold\Console\CreateModel;
use October\Rain\Scaffold\Console\CreateMigration;
use October\Rain\Scaffold\Console\CreateController;
use October\Rain\Scaffold\Console\CreateComponent;
use October\Rain\Scaffold\Console\CreateFormWidget;
use October\Rain\Scaffold\Console\CreateReportWidget;
use October\Rain\Scaffold\Console\CreateFilterWidget;
use October\Rain\Scaffold\Console\CreateContentField;
use October\Rain\Scaffold\Console\CreateSeeder;
use October\Rain\Scaffold\Console\CreateTest;
use October\Rain\Scaffold\Console\CreateJob;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

/**
 * ScaffoldServiceProvider
 */
class ScaffoldServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->app->singleton('command.create.plugin', CreatePlugin::class);
        $this->app->singleton('command.create.model', CreateModel::class);
        $this->app->singleton('command.create.migration', CreateMigration::class);
        $this->app->singleton('command.create.controller', CreateController::class);
        $this->app->singleton('command.create.component', CreateComponent::class);
        $this->app->singleton('command.create.formwidget', CreateFormWidget::class);
        $this->app->singleton('command.create.reportwidget', CreateReportWidget::class);
        $this->app->singleton('command.create.filterwidget', CreateFilterWidget::class);
        $this->app->singleton('command.create.contentfield', CreateContentField::class);
        $this->app->singleton('command.create.command', CreateCommand::class);
        $this->app->singleton('command.create.test', CreateTest::class);
        $this->app->singleton('command.create.job', CreateJob::class);
        $this->app->singleton('command.create.factory', CreateFactory::class);
        $this->app->singleton('command.create.seeder', CreateSeeder::class);

        $this->commands('command.create.plugin');
        $this->commands('command.create.model');
        $this->commands('command.create.migration');
        $this->commands('command.create.controller');
        $this->commands('command.create.component');
        $this->commands('command.create.formwidget');
        $this->commands('command.create.reportwidget');
        $this->commands('command.create.filterwidget');
        $this->commands('command.create.contentfield');
        $this->commands('command.create.command');
        $this->commands('command.create.test');
        $this->commands('command.create.job');
        $this->commands('command.create.factory');
        $this->commands('command.create.seeder');
    }

    /**
     * provides the returned services.
     * @return array
     */
    public function provides()
    {
        return [
            'command.create.plugin',
            'command.create.model',
            'command.create.migration',
            'command.create.controller',
            'command.create.component',
            'command.create.formwidget',
            'command.create.reportwidget',
            'command.create.filterwidget',
            'command.create.contentfield',
            'command.create.command',
            'command.create.test',
            'command.create.job',
            'command.create.factory',
            'command.create.seeder',
        ];
    }
}
