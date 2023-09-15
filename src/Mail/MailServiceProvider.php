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
     * registerIlluminateMailer instance, as a copy of parent with extensibility.
     */
    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mail.manager', function ($app) {
            // @deprecated use mailer.beforeResolve or callBeforeResolving
            $this->app['events']->dispatch('mailer.beforeRegister', [$this]);

            // Inheritance
            $manager = new MailManager($app);

            // @deprecated use mailer.resolve or callAfterResolving
            $this->app['events']->dispatch('mailer.register', [$this, $manager]);

            return $manager;
        });

        $this->app->bind('mailer', function ($app) {
            return $app->make('mail.manager')->mailer();
        });
    }
}
