<?php namespace October\Rain\Mail;

use Illuminate\Mail\MailServiceProvider as MailServiceProviderBase;

class MailServiceProvider extends MailServiceProviderBase
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        /*
         * Extensibility
         */
        $this->app['events']->fire('mailer.beforeRegister', [$this]);

        $this->registerSwiftMailer();

        $this->registerIlluminateMailer();

        $this->registerMarkdownRenderer();

        /*
         * Extensibility
         */
        $this->app['events']->fire('mailer.register', [$this, $this->app['mailer']]);
    }
}
