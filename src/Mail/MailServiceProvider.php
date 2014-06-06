<?php namespace October\Rain\Mail;

use Illuminate\Mail\MailServiceProvider as MailServiceProviderBase;

class MailServiceProvider extends MailServiceProviderBase
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['events']->fire('mailer.beforeRegister');

        $this->app->bindShared('mailer', function($app) {

            $this->registerSwiftMailer();

            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
            $mailer = new Mailer($app['view'], $app['swift.mailer']);

            $mailer->setLogger($app['log'])->setQueue($app['queue']);

            $mailer->setContainer($app);

            // If a "from" address is set, we will set it on the mailer so that all mail
            // messages sent by the applications will utilize the same "from" address
            // on each one, which makes the developer's life a lot more convenient.
            $from = $app['config']['mail.from'];

            if (is_array($from) && isset($from['address']))
            {
                $mailer->alwaysFrom($from['address'], $from['name']);
            }

            // Here we will determine if the mailer should be in "pretend" mode for this
            // environment, which will simply write out e-mail to the logs instead of
            // sending it over the web, which is useful for local dev environments.
            $pretend = $app['config']->get('mail.pretend', false);

            $mailer->pretend($pretend);

            return $mailer;
        });

        $this->app['events']->fire('mailer.register');
    }
}
