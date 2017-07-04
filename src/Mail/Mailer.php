<?php namespace October\Rain\Mail;

use Event;
use Config;
use Illuminate\Mail\Mailer as MailerBase;
use Illuminate\Contracts\Mail\Mailable as MailableContract;

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
     * @var string Original driver before pretending.
     */
    protected $pretendingOriginal;

    /**
     * Send a new message using a view.
     *
     * @param  string|array $view
     * @param  array $data
     * @param  \Closure|string $callback
     * @return mixed
     */
    public function send($view, array $data = [], $callback = null)
    {
        /*
         * Extensibility
         */
        if (
            ($this->fireEvent('mailer.beforeSend', [$view, $data, $callback], true) === false) ||
            (Event::fire('mailer.beforeSend', [$view, $data, $callback], true) === false)
        ) {
            return;
        }

        if ($view instanceof MailableContract) {
            return $this->sendMailable($view);
        }

        /*
         * Inherit logic from Illuminate\Mail\Mailer
         */
        list($view, $plain, $raw) = $this->parseView($view);

        $data['message'] = $message = $this->createMessage();

        if (is_bool($raw) && $raw === true) {
            $this->addContentRaw($message, $view, $plain);
        }
        else {
            $this->addContent($message, $view, $plain, $raw, $data);
        }

        call_user_func($callback, $message);

        if (isset($this->to['address'])) {
            $this->setGlobalTo($message);
        }

        /*
         * Extensibility
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
        $this->sendSwiftMessage($message->getSwiftMessage());
        $this->dispatchSentEvent($message);

        /*
         * Extensibility
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
     * @param  array $recipients
     * @param  string|array $view
     * @param  array $data
     * @param  \Closure|string $callback
     * @param  boolean $queue
     * @return void
     */
    public function sendTo($recipients, $view, array $data = [], $callback = null, $queue = false)
    {
        if (is_bool($callback)) {
            $queue = $callback;
        }

        $method = $queue === true ? 'queue' : 'send';
        $recipients = $this->processRecipients($recipients);

        return $this->{$method}($view, $data, function($message) use ($recipients, $callback) {
            foreach ($recipients as $address => $name) {
                $message->to($address, $name);
            }

            if (is_callable($callback)) {
                $callback($message);
            }
        });
    }

    /**
     * Helper for raw() method, send a new message when only a raw text part.
     * @param  array $recipients
     * @param  string  $view
     * @param  mixed   $callback
     * @param  boolean $queue
     * @return int
     */
    public function rawTo($recipients, $view, $callback = null, $queue = false)
    {
        if (!is_array($view)) {
            $view = ['text' => $view];
        }

        $view['raw'] = true;

        return $this->sendTo($recipients, $view, [], $callback, $queue);
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
         * Extensibility
         */
        if (
            ($this->fireEvent('mailer.beforeAddContent', [$message, $view, $data], true) === false) ||
            (Event::fire('mailer.beforeAddContent', [$this, $message, $view, $data], true) === false)
        ) {
            return;
        }

        $html = null;
        $text = null;

        if (isset($view)) {
            $viewContent = $this->getView($view, $data);
            $result = MailParser::parse($viewContent);
            $html = $result['html'];

            if ($result['text']) {
                $text = $result['text'];
            }

            /*
             * Subject
             */
            $customSubject = $message->getSwiftMessage()->getSubject();
            if (
                empty($customSubject) &&
                ($subject = array_get($result['settings'], 'subject'))
            ) {
                $message->subject($subject);
            }
        }

        if (isset($plain)) {
            $text = $this->getView($plain, $data);
        }

        if (isset($raw)) {
            $text = $raw;
        }

        $this->addContentRaw($message, $html, $text);

        /*
         * Extensibility
         */
        $this->fireEvent('mailer.addContent', [$message, $view, $data]);
        Event::fire('mailer.addContent', [$this, $message, $view, $data]);
    }

    /**
     * Add the raw content to a given message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @param  string  $html
     * @param  string  $text
     * @return void
     */
    protected function addContentRaw($message, $html, $text)
    {
        if (isset($html)) {
            $message->setBody($html, 'text/html');
        }

        if (isset($text)) {
            $message->addPart($text, 'text/plain');
        }
    }

    /**
     * Tell the mailer to not really send messages.
     *
     * @param  bool  $value
     * @return void
     */
    public function pretend($value = true)
    {
        if ($value) {
            $this->pretendingOriginal = Config::get('mail.driver');

            Config::set('mail.driver', 'log');
        }
        else {
            Config::set('mail.driver', $this->pretendingOriginal);
        }
    }
}
