<?php namespace October\Rain\Support;

use App;

/**
 * Simple flash messages
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class FlashMessages
{
    use \October\Rain\Support\Traits\Singleton;

    const SESSION_KEY = '_flash';

    /**
     * Session instance.
     */
    protected $session;

    /**
     * @var array A collection of flash messages.
     */
    public $messages = [];

    /**
     * @var array A collection of FlashBag objects.
     */
    public $flashBags = [];

    /**
     * Initialize this singleton.
     */
    protected function init()
    {
        $this->session = App::make('session');

        if (!$this->session->has(self::SESSION_KEY))
            return;

        $this->messages = $this->session->get(self::SESSION_KEY);
        foreach ($this->messages as $type => $message) {
            $this->add($message, $type);
        }

        $this->purge();
    }

    //
    // Helper access
    //

    /**
     * Returns all flash messages
     */
    public static function all()
    {
        return self::instance()->flashBags;
    }

    /**
     * Gets a flash message of a certain type (error, success, etc)
     */
    public static function get($type = null, $default = null)
    {
        if ($type === null)
            return self::first();

        $bags = self::all();
        if (isset($bags[$type]))
            return $bags[$type];

        return $default;
    }

    /**
     * Determine if messages exist for a given key.
     *
     * @param  string  $key
     * @return bool
     */
    public static function has($type = null)
    {
        if ($type !== null)
            return self::get($type) !== null;
        else
            return count(self::all() > 0);
    }

    /**
     * Get the first message.
     *
     * @param  string  $key
     * @param  string  $format
     * @return string
     */
    public static function first()
    {
        $messages = self::all();
        return (count($messages) > 0) ? reset($messages) : null;
    }

    /**
     * Gets / Sets an error message
     */
    public static function error($message = null)
    {
        if ($message === null)
            return self::get(FlashBag::ERROR);
        else
            return self::message($message, FlashBag::ERROR);
    }

    /**
     * Sets Gets / a success message
     */
    public static function success($message = null)
    {
        if ($message === null)
            return self::get(FlashBag::SUCCESS);
        else
            return self::message($message, FlashBag::SUCCESS);
    }

    /**
     * Gets / Sets a warning message
     */
    public static function warning($message = null)
    {
        if ($message === null)
            return self::get(FlashBag::WARNING);
        else
            return self::message($message, FlashBag::WARNING);
    }

    /**
     * Gets / Sets a information message
     */
    public static function info($message = null)
    {
        if ($message === null)
            return self::get(FlashBag::INFO);
        else
            return self::message($message, FlashBag::INFO);
    }

    /**
     * Sets a flash message of a certain type (error, success, etc)
     */
    public static function message($message, $type = null)
    {
        $obj = self::instance();
        $obj->add($message, $type);
    }

    /**
     * Forget a specific message type or everything.
     */
    public static function forget($type = null)
    {
        $obj = self::instance();
        $obj->discard($type);
    }

    //
    // Advanced internals
    //

    public function add($message, $type)
    {
        if (is_null($type))
            $type = FlashBag::INFO;

        if (!isset($this->flashBags[$type]))
            $this->flashBags[$type] = new FlashBag([], $type);

        $bag = $this->flashBags[$type];
        $bag->add(count($bag) + 1, $message);
    }

    /**
     * Stores the flash data to the session.
     * @param string $key Specifies a key to store, optional
     */
    public function store($key = null)
    {
        $this->messages = [];
        foreach ($this->flashBags as $type => $bag) {
            $this->messages[$type] = $bag->toArray();
        }

        $this->session->put(self::SESSION_KEY, $this->messages);
    }

    /**
     * Removes an object with a specified key or erases the flash data.
     * @param string $key Specifies a key to remove, optional
     */
    public function discard($key = null)
    {
        if ($key === null)
            $this->flashBags = [];
        else {
            unset($this->flashBags[$key]);
        }

        $this->store();
    }

    /*
     * Removes all flash data from the session.
     */
    public function purge()
    {
        $this->session->remove(self::SESSION_KEY);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->messages;
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert the message bag to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

}