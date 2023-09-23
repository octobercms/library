<?php namespace October\Rain\Mail;

use Illuminate\Mail\MailManager as MailManagerBase;
use InvalidArgumentException;

/**
 * MailManager class for sending mail.
 *
 * @package october\mail
 * @author Alexey Bobkov, Samuel Georges
 */
class MailManager extends MailManagerBase
{
    /**
     * resolve the given mailer. Copy of parent method, replacing Mailer class.
     * @param  string  $name
     * @return \Illuminate\Mail\Mailer
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        // Extensibility
        $this->app['events']->dispatch('mailer.beforeResolve', [$this, $name]);

        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Mailer [{$name}] is not defined.");
        }

        // Once we have created the mailer instance we will set a container instance
        // on the mailer. This allows us to resolve mailer classes via containers
        // for maximum testability on said classes instead of passing Closures.
        $mailer = new Mailer(
            $name,
            $this->app['view'],
            $this->createSymfonyTransport($config),
            $this->app['events']
        );

        if ($this->app->bound('queue')) {
            $mailer->setQueue($this->app['queue']);
        }

        // Next we will set all of the global addresses on this mailer, which allows
        // for easy unification of all "from" addresses as well as easy debugging
        // of sent messages since these will be sent to a single email address.
        foreach (['from', 'reply_to', 'to', 'return_path'] as $type) {
            $this->setGlobalAddress($mailer, $config, $type);
        }

        // Extensibility
        $this->app['events']->dispatch('mailer.resolve', [$this, $name, $mailer]);

        return $mailer;
    }
}
