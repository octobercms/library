<?php namespace October\Rain\Support\Facades;

use October\Rain\Mail\FakeMailer;
use Illuminate\Support\Facades\Mail as MailBase;

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
}
