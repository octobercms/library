<?php namespace October\Rain\Foundation\Providers;

use October\Rain\Foundation\Console\KeyGenerateCommand;
use Illuminate\Foundation\Providers\ArtisanServiceProvider as ArtisanServiceProviderBase;

class ArtisanServiceProvider extends ArtisanServiceProviderBase
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerKeyGenerateCommand()
    {
        $this->app->singleton('command.key.generate', function($app)
        {
            return new KeyGenerateCommand($app['files']);
        });
    }
}
