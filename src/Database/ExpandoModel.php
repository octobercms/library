<?php namespace October\Rain\Database;

/**
 * ExpandoModel treats all attributes as dynamic that are serialized to a single JSON column
 * in the database. This is useful for settings and user preference model base classes.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class ExpandoModel extends Model
{
    /**
     * @var string expandoColumn name to store the data
     */
    protected $expandoColumn = 'value';

    /**
     * @var array expandoPassthru attributes that should not be serialized
     */
    protected $expandoPassthru = [];

    /**
     * __construct
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->bindEvent('model.afterFetch', [$this, 'expandoAfterFetch']);

        $this->bindEvent('model.afterSave', [$this, 'expandoAfterSave']);

        // Process attributes last for traits with attribute modifiers
        $this->bindEvent('model.beforeSaveDone', [$this, 'expandoBeforeSaveDone'], -1);

        $this->addJsonable($this->expandoColumn);
    }

    /**
     * expandoAfterFetch constructor event
     */
    public function expandoAfterFetch()
    {
        $this->attributes = array_merge((array) $this->{$this->expandoColumn}, $this->attributes);

        $this->syncOriginal();
    }

    /**
     * expandoBeforeSaveDone constructor event
     */
    public function expandoBeforeSaveDone()
    {
        $this->{$this->expandoColumn} = array_diff_key(
            $this->attributes,
            array_flip($this->getExpandoPassthru())
        );

        $this->attributes = array_diff_key($this->attributes, $this->{$this->expandoColumn});
    }

    /**
     * expandoAfterSave constructor event
     */
    public function expandoAfterSave()
    {
        $this->attributes = array_merge($this->{$this->expandoColumn}, $this->attributes);
    }

    /**
     * getExpandoPassthru
     */
    protected function getExpandoPassthru()
    {
        return array_merge([$this->getKeyName(), $this->expandoColumn], $this->expandoPassthru);
    }
}
