<?php namespace October\Rain\Flash;

use App;
use Illuminate\Support\MessageBag;

/**
 * Simple flash bag
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class FlashBag extends MessageBag
{
    const INFO = 'info';
    const ERROR = 'error';
    const SUCCESS = 'success';
    const WARNING = 'warning';

    const SESSION_KEY = '_flash_oc';

    /**
     * All of the newly registered messages.
     *
     * @var array
     */
    protected $newMessages = [];

    /**
     * Session instance.
     */
    protected $session;

    public function __construct(array $messages = [])
    {
        parent::__construct($messages);

        $this->session = App::make('session');

        if ($this->session->has(self::SESSION_KEY)) {
            $this->messages = $this->session->get(self::SESSION_KEY);
        }

        $this->purge();
    }

    /**
     * Checks to see if any message is available.
     * @return bool
     */
    public function check()
    {
        return $this->any();
    }

    /**
     * Get first message for every key in the bag.
     * @param string|null $format
     * @return array
     */
    public function all($format = null)
    {
        $all = [];

        foreach ($this->messages as $key => $messages) {
            $all[$key] = reset($messages);
        }

        $this->purge();

        return $all;
    }

    /**
     * Gets all the flash messages of a given type.
     * @param string $key
     * @param string|null $format
     * @return array
     */
    public function get($key, $format = null)
    {
        $message = parent::get($key, $format);

        $this->purge();

        return $message;
    }

    /**
     * Gets / Sets an error message
     * @param string|null $message
     * @return array|FlashBag
     */
    public function error($message = null)
    {
        if ($message === null) {
            return $this->get(FlashBag::ERROR);
        }

        return $this->add(FlashBag::ERROR, $message);
    }

    /**
     * Sets Gets / a success message
     * @param string|null $message
     * @return array|FlashBag
     */
    public function success($message = null)
    {
        if ($message === null) {
            return $this->get(FlashBag::SUCCESS);
        }

        return $this->add(FlashBag::SUCCESS, $message);
    }

    /**
     * Gets / Sets a warning message
     * @param string|null $message
     * @return array|FlashBag
     */
    public function warning($message = null)
    {
        if ($message === null) {
            return $this->get(FlashBag::WARNING);
        }

        return $this->add(FlashBag::WARNING, $message);
    }

    /**
     * Gets / Sets a information message
     * @param string|null $message
     * @return array|FlashBag
     */
    public function info($message = null)
    {
        if ($message === null) {
            return $this->get(FlashBag::INFO);
        }

        return $this->add(FlashBag::INFO, $message);
    }

    /**
     * Add a message to the bag and stores it in the session.
     *
     * @param  string  $key
     * @param  string  $message
     * @return \October\Rain\Flash\FlashBag
     */
    public function add($key, $message)
    {
        $this->newMessages[$key][] = $message;

        $this->store();

        return parent::add($key, $message);
    }

    /**
     * Stores the flash data to the session.
     */
    public function store()
    {
        $this->session->put(self::SESSION_KEY, $this->newMessages);
    }

    /**
     * Removes an object with a specified key or erases the flash data.
     * @param string $key Specifies a key to remove, optional
     */
    public function forget($key = null)
    {
        if ($key === null) {
            $this->newMessages = $this->messages = [];
            $this->purge();
        }
        else {
            if (isset($this->messages[$key])) {
                unset($this->messages[$key]);
            }

            if (isset($this->newMessages[$key])) {
                unset($this->newMessages[$key]);
            }

            $this->store();
        }
    }

    /*
     * Removes all flash data from the session.
     */
    public function purge()
    {
        $this->session->remove(self::SESSION_KEY);
    }
}
