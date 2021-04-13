<?php namespace October\Rain\Mail;

use Event;
use Config;
use Illuminate\Mail\Mailer as MailerBase;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Support\Collection;

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
        /**
         * @event mailer.beforeSend
         * Fires before the mailer processes the sending action
         *
         * Example usage (stops the sending process):
         *
         *     Event::listen('mailer.beforeSend', function ((string|array) $view, (array) $data, (\Closure|string) $callback) {
         *         return false;
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.beforeSend', function ((string|array) $view, (array) $data, (\Closure|string) $callback) {
         *         return false;
         *     });
         *
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

        if ($callback !== null) {
            call_user_func($callback, $message);
        }

        if (is_bool($raw) && $raw === true) {
            $this->addContentRaw($message, $view, $plain);
        }
        else {
            $this->addContent($message, $view, $plain, $raw, $data);
        }

        if (isset($this->to['address'])) {
            $this->setGlobalToAndRemoveCcAndBcc($message);
        }

         /**
          * @event mailer.prepareSend
          * Fires before the mailer processes the sending action
          *
          * Parameters:
          * - $view: View code as a string
          * - $message: Illuminate\Mail\Message object, check Swift_Mime_SimpleMessage for useful functions.
          *
          * Example usage (stops the sending process):
          *
          *     Event::listen('mailer.prepareSend', function ((\October\Rain\Mail\Mailer) $mailerInstance, (string) $view, (\Illuminate\Mail\Message) $message) {
          *         return false;
          *     });
          *
          * Or
          *
          *     $mailerInstance->bindEvent('mailer.prepareSend', function ((string) $view, (\Illuminate\Mail\Message) $message) {
          *         return false;
          *     });
          *
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

        /**
         * @event mailer.send
         * Fires after the message has been sent
         *
         * Example usage (logs the message):
         *
         *     Event::listen('mailer.send', function ((\October\Rain\Mail\Mailer) $mailerInstance, (string) $view, (\Illuminate\Mail\Message) $message) {
         *         \Log::info("Message was rendered with $view and sent");
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.send', function ((string) $view, (\Illuminate\Mail\Message) $message) {
         *         \Log::info("Message was rendered with $view and sent");
         *     });
         *
         */
        $this->fireEvent('mailer.send', [$view, $message]);
        Event::fire('mailer.send', [$this, $view, $message]);
    }

    /**
     * Helper for send() method, the first argument can take a single email or an
     * array of recipients where the key is the address and the value is the name.
     *
     * @param  array $recipients
     * @param  string|array $view
     * @param  array $data
     * @param  mixed $callback
     * @param  array $options
     * @return void
     */
    public function sendTo($recipients, $view, array $data = [], $callback = null, $options = [])
    {
        if ($callback && !$options && !is_callable($callback)) {
            $options = $callback;
        }

        if (is_bool($options)) {
            $queue = $options;
            $bcc = false;
        }
        else {
            extract(array_merge([
                'queue' => false,
                'bcc'   => false
            ], $options));
        }

        $method = $queue === true ? 'queue' : 'send';
        $recipients = $this->processRecipients($recipients);

        return $this->{$method}($view, $data, function ($message) use ($recipients, $callback, $bcc) {

            $method = $bcc === true ? 'bcc' : 'to';

            foreach ($recipients as $address => $name) {
                $message->{$method}($address, $name);
            }

            if (is_callable($callback)) {
                $callback($message);
            }
        });
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($view, $data = null, $callback = null, $queue = null)
    {
        if (!$view instanceof MailableContract) {
            $mailable = $this->buildQueueMailable($view, $data, $callback, $queue);
            $queue = null;
        }
        else {
            $mailable = $view;
            $queue = $queue ?? $data;
        }

        return parent::queue($mailable, $queue);
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
     *
     * @param  string  $queue
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function queueOn($queue, $view, $data = null, $callback = null)
    {
        return $this->queue($view, $data, $callback, $queue);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  int  $delay
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $view, $data = null, $callback = null, $queue = null)
    {
        if (!$view instanceof MailableContract) {
            $mailable = $this->buildQueueMailable($view, $data, $callback, $queue);
            $queue = null;
        }
        else {
            $mailable = $view;
            $queue = $queue ?? $data;
        }

        return parent::later($delay, $mailable, $queue);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds on the given queue.
     *
     * @param  string  $queue
     * @param  int  $delay
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return mixed
     */
    public function laterOn($queue, $delay, $view, array $data = null, $callback = null)
    {
        return $this->later($delay, $view, $data, $callback, $queue);
    }

    /**
     * Build the mailable for a queued e-mail job.
     *
     * @param  mixed  $callback
     * @return mixed
     */
    protected function buildQueueMailable($view, $data, $callback, $queue)
    {
        $mailable = new Mailable;

        $mailable->view($view)->withSerializedData($data);

        if ($queue !== null) {
            $mailable->onQueue($queue);
        }

        if ($callback !== null) {
            call_user_func($callback, $mailable);
        }

        return $mailable;
    }

    /**
     * Send a new message when only a raw text part.
     *
     * @param  string  $text
     * @param  mixed  $callback
     * @return int
     */
    public function raw($view, $callback)
    {
        if (!is_array($view)) {
            $view = ['raw' => $view];
        }
        elseif (!array_key_exists('raw', $view)) {
            $view['raw'] = true;
        }

        return $this->send($view, [], $callback);
    }

    /**
     * Helper for raw() method, send a new message when only a raw text part.
     * @param  array $recipients
     * @param  string  $view
     * @param  mixed   $callback
     * @param  array   $options
     * @return int
     */
    public function rawTo($recipients, $view, $callback = null, $options = [])
    {
        if (!is_array($view)) {
            $view = ['raw' => $view];
        }
        elseif (!array_key_exists('raw', $view)) {
            $view['raw'] = true;
        }

        return $this->sendTo($recipients, $view, [], $callback, $options);
    }

    /**
     * Process a recipients object, which can look like the following:
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
        elseif (is_array($recipients) || $recipients instanceof Collection) {
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
        elseif (is_object($recipients)) {
            if (!empty($recipients->email) || !empty($recipients->address)) {
                $address = !empty($recipients->email) ? $recipients->email : $recipients->address;
                $name = !empty($recipients->name) ? $recipients->name : null;
                $result[$address] = $name;
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
     * @param  string $raw
     * @param  array $data
     * @return void
     */
    protected function addContent($message, $view, $plain, $raw, $data)
    {
        /**
         * @event mailer.beforeAddContent
         * Fires before the mailer adds content to the message
         *
         * Example usage (stops the content adding process):
         *
         *     Event::listen('mailer.beforeAddContent', function ((\October\Rain\Mail\Mailer) $mailerInstance, (\Illuminate\Mail\Message) $message, (string) $view, (string) $plain, (string) $raw, (array) $data) {
         *         return false;
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.beforeAddContent', function ((\Illuminate\Mail\Message) $message, (string) $view, (string) $plain, (string) $raw, (array) $data) {
         *         return false;
         *     });
         *
         */
        if (
            ($this->fireEvent('mailer.beforeAddContent', [$message, $view, $data, $raw, $plain], true) === false) ||
            (Event::fire('mailer.beforeAddContent', [$this, $message, $view, $data, $raw, $plain], true) === false)
        ) {
            return;
        }

        $html = null;
        $text = null;

        if (isset($view)) {
            $viewContent = $this->renderView($view, $data);
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
            $text = $this->renderView($plain, $data);
        }

        if (isset($raw)) {
            $text = $raw;
        }

        $this->addContentRaw($message, $html, $text);

        /**
         * @event mailer.addContent
         * Fires after the mailer has added content to the message
         *
         * Example usage (Logs that content has been added):
         *
         *     Event::listen('mailer.addContent', function ((\October\Rain\Mail\Mailer) $mailerInstance, (\Illuminate\Mail\Message) $message, (string) $view, (array) $data) {
         *         \Log::info("$view has had content added to the message");
         *     });
         *
         * Or
         *
         *     $mailerInstance->bindEvent('mailer.addContent', function ((\Illuminate\Mail\Message) $message, (string) $view, (array) $data) {
         *         \Log::info("$view has had content added to the message");
         *     });
         *
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
