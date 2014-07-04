<?php namespace October\Rain\Mail;

use Event;
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
     * Send a new message using a view.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  Closure|string  $callback
     * @return void
     */
    public function send($view, array $data, $callback)
    {
        /*
         * Inherit logic from Illuminate\Mail\Mailer
         */
        list($view, $plain) = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();
        $this->callMessageBuilder($callback, $message);
        $this->addContent($message, $view, $plain, $data);

        /*
         * Extensbility
         * $view    - View code as a string
         * $message - Illuminate\Mail\Message object,
         *            check Swift_Mime_SimpleMessage for useful functions.
         */
        if (Event::fire('mailer.beforeSend', [$this, $view, $message], true) === false) return;
        if ($this->fireEvent('mailer.beforeSend', [$view, $message], true) === false) return;

        /*
         * Send the message
         */
        $_message = $message->getSwiftMessage();
        $this->sendSwiftMessage($_message);

        /*
         * Extensbility
         */
        Event::fire('mailer.send', [$this, $view, $message]);
        $this->fireEvent('mailer.send', [$view, $message]);
    }

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
        /*
         * Extensbility
         */
        if (Event::fire('mailer.beforeAddContent', [$this, $message, $view, $plain, $data], true) === false) return;
        if ($this->fireEvent('mailer.beforeAddContent', [$message, $view, $plain, $data], true) === false) return;

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

        /*
         * Extensbility
         */
        Event::fire('mailer.addContent', [$this, $message, $view, $plain, $data]);
        $this->fireEvent('mailer.addContent', [$message, $view, $plain, $data]);
    }

}