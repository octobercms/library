<?php namespace October\Rain\Database\Traits;

/**
 * Defaultable adds default assignment to models
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Defaultable
{
    /**
     * @var static defaultableCache
     */
    protected static $defaultableCache;

    /**
     * initializeDefaultable
     */
    public function initializeDefaultable()
    {
        $this->bindEvent('model.afterSave', [$this, 'defaultableAfterSave']);
    }

    /**
     * defaultableAfterSave
     */
    public function defaultableAfterSave()
    {
        if ($this->is_default) {
            $this->makeDefault();
        }
    }

    /**
     * makeDefault
     */
    public function makeDefault()
    {
        $this->newQuery()->where('id', $this->id)->update(['is_default' => true]);
        $this->newQuery()->where('id', '<>', $this->id)->update(['is_default' => false]);
    }

    /**
     * getDefault returns the default product type.
     */
    public static function getDefault()
    {
        if (static::$defaultableCache !== null) {
            return static::$defaultableCache;
        }

        $defaultType = static::where('is_default', true)->first();

        // If no default is found, find the first record and make it the default.
        if (!$defaultType && ($defaultType = static::first())) {
            $defaultType->makeDefault();
        }

        return static::$defaultableCache = $defaultType;
    }
}
