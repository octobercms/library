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
        $this->app['events']->fire('mailer.beforeRegister');

        $this->app->bindShared('mailer', function($app) {

            $this->registerSwiftMailer();

            $mailer = new Mailer($app['view'], $app['swift.mailer']);
            $mailer->setLogger($app['log'])->setQueue($app['queue']);
            $mailer->setContainer($app);

            $from = $app['config']['mail.from'];
            if (is_array($from) && isset($from['address'])) {
                $mailer->alwaysFrom($from['address'], $from['name']);
            }

            $pretend = $app['config']->get('mail.pretend', false);
            $mailer->pretend($pretend);

            return $mailer;
        });

        $this->app['events']->fire('mailer.register');
    }
}
