<?php namespace October\Rain\Mail;

use Illuminate\Mail\MailServiceProvider as MailServiceProviderBase;

class MailServiceProvider extends MailServiceProviderBase
{
    /**
     * Register the Illuminate mailer instance. Carbon copy of Illuminate method.
     * @return void
     */
    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mailer', function ($app) {
            /*
             * Extensibility
             */
            $this->app['events']->fire('mailer.beforeRegister', [$this]);

            $config = $app->make('config')->get('mail');

            /*
             * October mailer
             */
            $mailer = new Mailer(
                $app['view'],
                $app['swift.mailer'],
                $app['events']
            );

            if ($app->bound('queue')) {
                $mailer->setQueue($app['queue']);
            }

            foreach (['from', 'reply_to', 'to'] as $type) {
                $this->setGlobalAddress($mailer, $config, $type);
            }

            /*
             * Extensibility
             */
            $this->app['events']->fire('mailer.register', [$this, $mailer]);

            return $mailer;
        });
    }
}
