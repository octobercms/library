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
     * fake the instance, the fake keeps the real mail manager when it's faked twice
     */
    public static function fake()
    {
        $actualMailManager = static::isFake()
            ? static::getFacadeRoot()->manager
            : static::getFacadeRoot();

        static::swap($fake = new FakeMailer($actualMailManager));

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
