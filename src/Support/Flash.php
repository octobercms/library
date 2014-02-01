<?php namespace October\Rain\Support;

use App;
use Countable;
use ArrayAccess;
use IteratorAggregate;

/**
 * Simple flash messages
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Flash implements ArrayAccess, IteratorAggregate, Countable
{
    use \October\Rain\Support\Traits\Singleton;

    const SESSION_KEY = '_flash';

    const TYPE_INFO = 'info';
    const TYPE_ERROR = 'error';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';

    protected $session;

    public $messages = [];

    /**
     * Initialize this singleton.
     */
    protected function init()
    {
        $this->session = App::make('session');

        if (!$this->session->has(self::SESSION_KEY))
            return;

        $this->messages = $this->session->get(self::SESSION_KEY);
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
        return self::instance()->messages;
    }

    /**
     * Gets a flash message of a certain type (error, success, etc)
     */
    public static function get($type = null, $default = null)
    {
        if ($type === null)
            return self::first();

        $obj = self::instance();
        if (isset($obj[$type]))
            return $obj[$type];

        return $default;
    }

    /**
     * Determine if messages exist for a given key.
     *
     * @param  string  $key
     * @return bool
     */
    public static function has($typr = null)
    {
        if ($typr !== null)
            return self::get($typr) !== null;
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
            return self::get(self::TYPE_ERROR);
        else
            return self::message(self::TYPE_ERROR, $message);
    }

    /**
     * Sets Gets / a success message
     */
    public static function success($message = null)
    {
        if ($message === null)
            return self::get(self::TYPE_SUCCESS);
        else
            return self::message(self::TYPE_SUCCESS, $message);
    }

    /**
     * Gets / Sets a warning message
     */
    public static function warning($message = null)
    {
        if ($message === null)
            return self::get(self::TYPE_WARNING);
        else
            return self::message(self::TYPE_WARNING, $message);
    }

    /**
     * Gets / Sets a information message
     */
    public static function info($message = null)
    {
        if ($message === null)
            return self::get(self::TYPE_INFO);
        else
            return self::message(self::TYPE_INFO, $message);
    }

    /**
     * Sets a flash message of a certain type (error, success, etc)
     */
    public static function message($type, $message = null)
    {
        $obj = self::instance();

        if ($message === null)
            $obj[] = $type;
        else
            $obj[$type] = $message;
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

    /**
     * Stores the flash data to the session.
     * @param string $key Specifies a key to store, optional
     */
    public function store($key = null)
    {
        if ($key === null)
            $this->session->put(self::SESSION_KEY, $this->messages);
        else
            $this->session->put(self::SESSION_KEY, [ $key => $this->messages[$key] ]);
    }

    /**
     * Removes an object with a specified key or erases the flash data.
     * @param string $key Specifies a key to remove, optional
     */
    public function discard($key = null)
    {
        if ($key === null)
            $this->messages = [];
        else
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

    //
    // Iterator implementation
    //

    public function offsetExists($offset)
    {
        return isset($this->messages[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset))
            return $this->messages[$offset];
        else
            return false;
    }

    public function offsetSet($offset, $value)
    {
        if ($offset)
            $this->messages[$offset] = $value;
        else
            $this->messages[] = $value;

        $this->store();
    }

    public function offsetUnset($offset)
    {
        unset($this->messages[$offset]);
        $this->store();
    }

    public function getIterator()
    {
        return new ArrayIterator($this->messages);
    }

    /**
    * Returns a number of stored flash items
    * @return integer
    */
    public function count()
    {
        return count($obj->messages);
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