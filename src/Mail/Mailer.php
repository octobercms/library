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
     * @param  string|array $view
     * @param  array $data
     * @param  Closure|string $callback
     * @return void
     */
    public function send($view, array $data, $callback)
    {
        /*
         * Extensbility
         */
        if (
            ($this->fireEvent('mailer.beforeSend', [$view, $data, $callback], true) === false) ||
            (Event::fire('mailer.beforeSend', [$view, $data, $callback], true) === false)
        ) {
            return;
        }

        /*
         * Inherit logic from Illuminate\Mail\Mailer
         */
        list($view, $plain, $raw) = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();
        $this->callMessageBuilder($callback, $message);
        $this->addContent($message, $view, $plain, $raw, $data);

        /*
         * Extensbility
         * $view    - View code as a string
         * $message - Illuminate\Mail\Message object,
         *            check Swift_Mime_SimpleMessage for useful functions.
         */
        if (
            ($this->fireEvent('mailer.prepareSend', [$view, $message], true) === false) ||
            (Event::fire('mailer.prepareSend', [$this, $view, $message], true) === false)
        ) {
            return;
        }

        /*
         * Send the message
         */
        $_message = $message->getSwiftMessage();
        $this->sendSwiftMessage($_message);

        /*
         * Extensbility
         */
        $this->fireEvent('mailer.send', [$view, $message]);
        Event::fire('mailer.send', [$this, $view, $message]);
    }

    /**
     * Helper for send() method, the first argument can take a single email or an
     * array of recipients where the key is the address and the value is the name.
     * The callback argument can be a boolean that when TRUE will use queue() to
     * send the message instead. The callback argument can also be an array of options
     * with the following (@todo):
     *  - queue
     *  - queueName
     *  - callback
     *  - delay
     * @param  string|array $view
     * @param  array $data
     * @param  Closure|string $callback
     * @param  boolean $queue
     * @return void
     */
    public function sendTo($recipients, $view, array $data = [], $callback = null, $queue = false)
    {
        if (is_bool($callback))
            $queue = $callback;

        $method = $queue === true ? 'queue' : 'send';
        $recipients = $this->processRecipients($recipients);

        $this->{$method}($view, $data, function($message) use ($recipients, $callback) {
            foreach ($recipients as $address => $name) {
                $message->to($address, $name);
            }

            if (is_callable($callback)) {
                $callback($message);
            }
        });
    }

    /**
     * Process a receipients object, which can look like the following:
     *  - (string) admin@domain.tld
     *  - (object) ['email' => 'admin@domain.tld', 'name' => 'Adam Person']
     *  - (array) ['admin@domain.tld' => 'Adam Person', ...]
     *  - (array) [ (object|array) ['email' => 'admin@domain.tld', 'name' => 'Adam Person'], [...] ]
     * @param mixed $recipients
     * @return array
     */
    protected function processRecipients($recipients)
    {
        $result = [];

        if (is_string($recipients)) {
            $result[$recipients] = null;
        }
        elseif (is_object($recipients)) {
            if (!empty($recipients->email) || !empty($recipients->address)) {
                $address = !empty($recipients->email) ? $recipients->email : $recipients->address;
                $name = !empty($recipients->name) ? $recipients->name : null;
                $result[$address] = $name;
            }
        }
        elseif (is_array($recipients) || $recipients instanceof \ArrayAccess) {

            foreach ($recipients as $address => $person) {

                if (is_string($person)) {
                    $result[$address] = $person;
                }
                elseif (is_object($person)) {
                    if (empty($person->email) && empty($person->address)) {
                        continue;
                    }

                    $address = !empty($person->email) ? $person->email : $person->address;
                    $name = !empty($person->name) ? $person->name : null;
                    $result[$address] = $name;
                }
                elseif (is_array($person)) {
                    if (!$address = array_get($person, 'email', array_get($person, 'address'))) {
                        continue;
                    }

                    $result[$address] = array_get($person, 'name');
                }

            }
        }

        return $result;
    }

    /**
     * Add the content to a given message.
     *
     * @param  \Illuminate\Mail\Message $message
     * @param  string $view
     * @param  string $plain
     * @param  array $data
     * @return void
     */
    protected function addContent($message, $view, $plain, $raw, $data)
    {
        /*
         * Extensbility
         */
        if (
            ($this->fireEvent('mailer.beforeAddContent', [$message, $view, $data], true) === false) ||
            (Event::fire('mailer.beforeAddContent', [$this, $message, $view, $data], true) === false)
        ) {
            return;
        }

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

        if (isset($raw)) {
            $message->addPart($raw, 'text/plain');
        }

        /*
         * Extensbility
         */
        $this->fireEvent('mailer.addContent', [$message, $view, $data]);
        Event::fire('mailer.addContent', [$this, $message, $view, $data]);
    }

}