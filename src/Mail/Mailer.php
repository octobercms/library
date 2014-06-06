<?php namespace October\Rain\Mail;

use Illuminate\Mail\Mailer as MailerBase;

/**
 * Mailer class for sending mail.
 *
 * @package october\mail
 * @author Alexey Bobkov, Samuel Georges
 */
class Mailer extends MailerBase
{

    use \October\Rain\Support\Traits\Emitter;

    /**
     * Add the content to a given message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  string  $view
     * @param  string  $plain
     * @param  array   $data
     * @return void
     */
    protected function addContent($message, $view, $plain, $data)
    {
        if ($this->fireEvent('content.beforeAdd', [$message, $view, $plain, $data], true) === false)
            return;

        if (isset($view)) {
            $viewContent = $this->getView($view, $data);
            $result = MailParser::parse($viewContent);
            $message->setBody($result['html'], 'text/html');

            if ($result['text'])
                $message->addPart($result['text'], 'text/plain');

            if ($subject = array_get($result['settings'], 'subject'))
                $message->subject($subject);
        }

        if (isset($plain)) {
            $message->addPart($this->getView($plain, $data), 'text/plain');
        }

        $this->fireEvent('content.add', [$message, $view, $plain, $data]);
    }

}