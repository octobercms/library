<?php

namespace October\Rain\Support;

use Illuminate\Support\DefaultProviders as DefaultProvidersBase;

class DefaultProviders extends DefaultProvidersBase
{
    /**
     * Create a new default provider collection.
     *
     * @return void
     */
    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?: [
            // October Providers
            //
            \October\Rain\Foundation\Providers\AppServiceProvider::class,
            \October\Rain\Foundation\Providers\DateServiceProvider::class,
            \October\Rain\Database\DatabaseServiceProvider::class,
            \October\Rain\Halcyon\HalcyonServiceProvider::class,
            \October\Rain\Filesystem\FilesystemServiceProvider::class,
            \October\Rain\Html\UrlServiceProvider::class,

            // October Providers (Deferred)
            \October\Rain\Mail\MailServiceProvider::class,
            \October\Rain\Html\HtmlServiceProvider::class,
            \October\Rain\Flash\FlashServiceProvider::class,
            \October\Rain\Parse\ParseServiceProvider::class,
            \October\Rain\Assetic\AsseticServiceProvider::class,
            \October\Rain\Resize\ResizeServiceProvider::class,
            \October\Rain\Validation\ValidationServiceProvider::class,
            \October\Rain\Translation\TranslationServiceProvider::class,
            \Illuminate\Auth\Passwords\PasswordResetServiceProvider:: class,

            // October Console (Deferred)
            \October\Rain\Foundation\Providers\ArtisanServiceProvider::class,
            \October\Rain\Database\MigrationServiceProvider::class,
            \October\Rain\Scaffold\ScaffoldServiceProvider::class,
            \Illuminate\Foundation\Providers\ComposerServiceProvider::class,

            // Laravel Providers
            //
            \Illuminate\Broadcasting\BroadcastServiceProvider::class,
            \Illuminate\Bus\BusServiceProvider::class,
            \Illuminate\Cache\CacheServiceProvider::class,
            \Illuminate\Concurrency\ConcurrencyServiceProvider::class,
            \Illuminate\Cookie\CookieServiceProvider::class,
            \Illuminate\Encryption\EncryptionServiceProvider::class,
            \Illuminate\Foundation\Providers\FoundationServiceProvider::class,
            \Illuminate\Hashing\HashServiceProvider::class,
            \Illuminate\Pagination\PaginationServiceProvider::class,
            \Illuminate\Pipeline\PipelineServiceProvider::class,
            \Illuminate\Queue\QueueServiceProvider::class,
            \Illuminate\Redis\RedisServiceProvider::class,
            \Illuminate\Session\SessionServiceProvider::class,
            \Illuminate\View\ViewServiceProvider::class,
        ];
    }
}
