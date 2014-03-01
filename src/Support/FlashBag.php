<?php namespace October\Rain\Support;

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

    const SESSION_KEY = '_flash';

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

    public function __construct(array $messages = array())
    {
        parent::__construct($messages);

        $this->session = App::make('session');

        if ($this->session->has(self::SESSION_KEY))
            $this->messages = $this->session->get(self::SESSION_KEY);

        $this->purge();
    }

    /**
     * Get first message for every key in the bag.
     *
     * @param  string  $format
     * @return array
     */
    public function all($format = null)
    {
        $all = [];
        foreach ($this->messages as $key => $messages) {
            $all[$key] = reset($messages);
        }

        return $all;
    }

    /**
     * Gets / Sets an error message
     */
    public function error($message = null)
    {
        if ($message === null)
            return $this->get(FlashBag::ERROR);
        else
            return $this->add(FlashBag::ERROR, $message);
    }

    /**
     * Sets Gets / a success message
     */
    public function success($message = null)
    {
        if ($message === null)
            return $this->get(FlashBag::SUCCESS);
        else
            return $this->add(FlashBag::SUCCESS, $message);
    }

    /**
     * Gets / Sets a warning message
     */
    public function warning($message = null)
    {
        if ($message === null)
            return $this->get(FlashBag::WARNING);
        else
            return $this->add(FlashBag::WARNING, $message);
    }

    /**
     * Gets / Sets a information message
     */
    public function info($message = null)
    {
        if ($message === null)
            return $this->get(FlashBag::INFO);
        else
            return $this->add(FlashBag::INFO, $message);
    }

    /**
     * Add a message to the bag and stores it in the session.
     *
     * @param  string  $key
     * @param  string  $message
     * @return \October\Rain\Support\FlashBag
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
    public function discard($key = null)
    {
        if ($this->flashBag === null)
            return;

        if ($key === null)
            $this->messages = [];

        elseif (isset($this->messages[$key]))
            unset($this->messages[$key]);

        $this->store();
    }

    /*
     * Removes all flash data from the session.
     */
    public function purge()
    {
        $this->session->remove(self::SESSION_KEY);
    }

}