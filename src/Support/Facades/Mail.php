<?php namespace October\Rain\Support\Facades;

use October\Rain\Mail\FakeMailer;
use Illuminate\Support\Facades\Mail as MailBase;

/**
 * Mail
 *
 * @method static void sendTo(mixed $recipients, string $view, array $data = [], $callback = null, $options = [])
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
