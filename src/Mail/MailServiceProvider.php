<?php namespace October\Rain\Mail;

use Illuminate\Mail\MailServiceProvider as MailServiceProviderBase;

class MailServiceProvider extends MailServiceProviderBase
{
    /**
     * @var bool Indicates if loading of the provider is deferred.
     */
    protected $defer = true;

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {

        $this->app->bindShared('mailer', function($app) {

            /*
             * Extensibility
             */
            $this->app['events']->fire('mailer.beforeRegister', [$this]);

            /*
             * Inherit logic from Illuminate\Mail\MailServiceProvider
             */
            $this->registerSwiftMailer();

            $mailer = new Mailer($app['view'], $app['swift.mailer'], $app['events']);

            $this->setMailerDependencies($mailer, $app);

            $from = $app['config']['mail.from'];
            if (is_array($from) && isset($from['address'])) {
                $mailer->alwaysFrom($from['address'], $from['name']);
            }

            $pretend = $app['config']->get('mail.pretend', false);
            $mailer->pretend($pretend);

            /*
             * Extensibility
             */
            $this->app['events']->fire('mailer.register', [$this, $mailer]);

            return $mailer;
        });

    }
}
