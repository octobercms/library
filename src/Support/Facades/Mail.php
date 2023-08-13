<?php namespace October\Rain\Support\Facades;

use Illuminate\Support\Facades\Mail as MailBase;
use October\Rain\Mail\FakeMailer;

/**
 * Mail
 *
 * @see \October\Rain\Mails\Dispatcher
 */
class Mail extends MailBase
{
    /**
     * fake the instance
     */
    public static function fake()
    {
        static::swap($fake = new FakeMailer);

        return $fake;
    }

    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mail.manager';
    }
}
