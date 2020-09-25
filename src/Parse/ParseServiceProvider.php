<?php namespace October\Rain\Parse;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class ParseServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        'parse.markdown' => Markdown::class,
        'parse.yaml' => Yaml::class,
        'parse.twig' => Twig::class,
        'parse.ini' => Ini::class,
    ];

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'parse.markdown',
            'parse.yaml',
            'parse.twig',
            'parse.ini',
        ];
    }
}
