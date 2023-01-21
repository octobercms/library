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
     * @var int expandoPriority events should come first but make room for others,
     * assuming a range of 1 to 1000.
     */
    protected $expandoPriority = 300;

    /**
     * __construct
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->bindEvent('model.afterFetch', [$this, 'expandoAfterFetch'], $this->expandoPriority);

        $this->bindEvent('model.saveInternal', [$this, 'expandoSaveInternal'], $this->expandoPriority);

        $this->bindEvent('model.afterSave', [$this, 'expandoAfterSave'], $this->expandoPriority);

        $this->addJsonable($this->expandoColumn);
    }

    /**
     * afterModelFetch constructor event
     */
    public function expandoAfterFetch()
    {
        $this->attributes = array_merge($this->{$this->expandoColumn}, $this->attributes);

        $this->syncOriginal();
    }

    /**
     * saveModelInternal constructor event
     */
    public function expandoSaveInternal()
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
