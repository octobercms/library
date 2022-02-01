<?php namespace October\Rain\Mail;

use Illuminate\Mail\MailServiceProvider as MailServiceProviderBase;

/**
 * MailServiceProvider
 *
 * @package october\mail
 * @author Alexey Bobkov, Samuel Georges
 */
class MailServiceProvider extends MailServiceProviderBase
{
    /**
     * registerIlluminateMailer instance. Copy of parent with extensibility.
     */
    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mail.manager', function ($app) {
            return new MailManager($app);
        });

        $this->app->bind('mailer', function ($app) {
            /*
             * Extensibility
             */
            $this->app['events']->dispatch('mailer.beforeRegister', [$this]);

            $mailer = $app->make('mail.manager')->mailer();

            /*
             * Extensibility
             */
            $this->app['events']->dispatch('mailer.register', [$this, $mailer]);

            return $mailer;
        });
    }
}
