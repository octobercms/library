<?php namespace October\Rain\Auth\Models;

use October\Rain\Database\Model;
use October\Rain\Auth\AuthException;
use October\Rain\Auth\Manager;

/**
 * Preferences model for a user
 */
class Preferences extends Model
{
    use \October\Rain\Support\Traits\KeyParser;

    /**
     * @var string table used by the model
     */
    protected $table = 'preferences';

    /**
     * @var bool timestamps enabled
     */
    public $timestamps = false;

    /**
     * @var array cache
     */
    protected static $cache = [];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = ['value'];

    /**
     * @var \October\Rain\Auth\Models\User userContext is the user that owns the preferences
     */
    public $userContext;

    /**
     * resolveUser checks for a supplied user or uses the default logged in. You should
     * override this method
     *
     * @param mixed $user An optional back-end user object.
     * @return User object
     */
    public function resolveUser($user)
    {
        $user = Manager::instance()->getUser();
        if (!$user) {
            throw new AuthException('User is not logged in');
        }

        return $user;
    }

    /**
     * forUser creates this object and sets the user context
     */
    public static function forUser($user = null)
    {
        $self = new static;
        $self->userContext = $user ?: $self->resolveUser($user);
        return $self;
    }

    /**
     * get returns a setting value by the module (or plugin) name and setting name
     * @param string $key Specifies the setting key value, for example 'backend:items.perpage'
     * @param mixed $default The default value to return if the setting doesn't exist in the DB.
     * @return mixed Returns the setting value loaded from the database or the default value.
     */
    public function get($key, $default = null)
    {
        if (!($user = $this->userContext)) {
            return $default;
        }

        $cacheKey = $this->getCacheKey($key, $user);

        if (array_key_exists($cacheKey, static::$cache)) {
            return static::$cache[$cacheKey];
        }

        $record = static::findRecord($key, $user);
        if (!$record) {
            return static::$cache[$cacheKey] = $default;
        }

        return static::$cache[$cacheKey] = $record->value;
    }

    /**
     * set stores a setting value to the database
     * @param string $key Specifies the setting key value, for example 'backend:items.perpage'
     * @param mixed $value The setting value to store, serializable.
     * If the user is not provided the currently authenticated user will be used. If there is no
     * an authenticated user, the exception will be thrown.
     * @return bool
     */
    public function set($key, $value)
    {
        if (!$user = $this->userContext) {
            return false;
        }

        $record = static::findRecord($key, $user);
        if (!$record) {
            list($namespace, $group, $item) = $this->parseKey($key);
            $record = new static;
            $record->namespace = $namespace;
            $record->group = $group;
            $record->item = $item;
            $record->user_id = $user->id;
        }

        $record->value = $value;
        $record->save();

        $cacheKey = $this->getCacheKey($key, $user);
        static::$cache[$cacheKey] = $value;
        return true;
    }

    /**
     * reset a setting value by deleting the record
     * @param string $key Specifies the setting key value.
     * @return bool
     */
    public function reset($key)
    {
        if (!$user = $this->userContext) {
            return false;
        }

        $record = static::findRecord($key, $user);
        if (!$record) {
            return false;
        }

        $record->delete();

        $cacheKey = $this->getCacheKey($key, $user);
        unset(static::$cache[$cacheKey]);

        return true;
    }

    /**
     * findRecord returns a record for a user
     * @return self
     */
    public static function findRecord($key, $user = null)
    {
        return static::applyKeyAndUser($key, $user)->first();
    }

    /**
     * scopeApplyKeyAndUser to find a setting record for the specified module (or plugin) name,
     * setting name and user.
     *
     * @param string $key Specifies the setting key value, for example 'backend:items.perpage'
     * @param mixed $default The default value to return if the setting doesn't exist in the DB.
     * @param mixed $user An optional user object.
     * @return mixed Returns the found record or null.
     */
    public function scopeApplyKeyAndUser($query, $key, $user = null)
    {
        list($namespace, $group, $item) = $this->parseKey($key);

        $query = $query
            ->where('namespace', $namespace)
            ->where('group', $group)
            ->where('item', $item);

        if ($user) {
            $query = $query->where('user_id', $user->id);
        }

        return $query;
    }

    /**
     * getCacheKey builds a cache key for the preferences record
     * @return string
     */
    protected function getCacheKey($item, $user)
    {
        return $user->id . '-' . $item;
    }
}
