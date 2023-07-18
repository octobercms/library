<?php namespace October\Rain\Foundation\Providers;

use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * AppDeferSupportServiceProvider supplies deferred providers
 */
class AppDeferSupportServiceProvider extends AggregateServiceProvider implements DeferrableProvider
{
    /**
     * provides gets the services provided by the provider
     */
    protected $providers = [
        // App
        \October\Rain\Mail\MailServiceProvider::class,
        \October\Rain\Html\HtmlServiceProvider::class,
        \October\Rain\Flash\FlashServiceProvider::class,
        \October\Rain\Parse\ParseServiceProvider::class,
        \October\Rain\Assetic\AsseticServiceProvider::class,
        \October\Rain\Resize\ResizeServiceProvider::class,
        \October\Rain\Validation\ValidationServiceProvider::class,
        \October\Rain\Translation\TranslationServiceProvider::class,
        \Illuminate\Auth\Passwords\PasswordResetServiceProvider:: class,

        // Console
        \October\Rain\Foundation\Providers\ArtisanServiceProvider::class,
        \October\Rain\Database\MigrationServiceProvider::class,
        \October\Rain\Scaffold\ScaffoldServiceProvider::class,
        \Illuminate\Foundation\Providers\ComposerServiceProvider::class
    ];
}
