<?php namespace October\Rain\Mail;

use Illuminate\Mail\TransportManager as TransportManagerBase;
use October\Rain\Mail\Transport\MailgunTransport;
use October\Rain\Mail\Transport\MandrillTransport;

class TransportManager extends TransportManagerBase
{
    /**
     * {@inheritdoc}
     */
    protected function createMailgunDriver()
    {
        $config = $this->app['config']->get('services.mailgun', array());

        return new MailgunTransport($config['secret'], $config['domain']);
    }

    /**
     * {@inheritdoc}
     */
    protected function createMandrillDriver()
    {
        $config = $this->app['config']->get('services.mandrill', array());

        return new MandrillTransport($config['secret']);
    }
}