<?php namespace October\Rain\Database\Behaviors;

class Purgeable extends \October\Rain\Extension\ExtensionBase
{
    /**
     * @var array List of attribute names which should not be saved to the database.
     *
     * public $purgeable = [];
     */

    protected $model;

    public function __construct($parent)
    {
        $this->model = $parent;
        $this->bootPurgeable();
    }

    /**
     * @var array List of original attribute values before they were purged.
     */
    protected $originalPurgeableValues = [];

    /**
     * Boot the purgeable trait for a model.
     * @return void
     */
    public function bootPurgeable()
    {
        if (!$this->model->propertyExists('purgeable')) {
            $this->model->addDynamicProperty('purgeable', []);
        }
        
        $this->model->purgeable[] = 'purgeable';
        $dynPropNames = array_keys(array_diff_key($this->model->getDynamicProperties(), ['purgeable' => 0]));
        $this->model->purgeable = array_merge($this->model->purgeable, $dynPropNames);

        /*
         * Remove any purge attributes from the data set
         */
        $model = $this->model;
        $model->bindEvent('model.saveInternal', function () use ($model) {
            $model->purgeAttributes();
        });
    }

    /**
     * Adds an attribute to the purgeable attributes list
     * @param  array|string|null  $attributes
     * @return $this
     */
    public function addPurgeable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->model->purgeable = array_merge($this->model->purgeable, $attributes);

        return $this->model;
    }

    /**
     * Removes purged attributes from the dataset, used before saving.
     * @param $attributes mixed Attribute(s) to purge, if unspecified, $purgable property is used
     * @return array Current attribute set
     */
    public function purgeAttributes($attributesToPurge = null)
    {
        if ($attributesToPurge !== null) {
            $purgeable = is_array($attributesToPurge) ? $attributesToPurge : [$attributesToPurge];
        }
        else {
            $purgeable = $this->getPurgeableAttributes();
        }

        $attributes = $this->model->getAttributes();
        $cleanAttributes = array_diff_key($attributes, array_flip($purgeable));
        $originalAttributes = array_diff_key($attributes, $cleanAttributes);

        if (is_array($this->originalPurgeableValues)) {
            $this->originalPurgeableValues = array_merge($this->originalPurgeableValues, $originalAttributes);
        }
        else {
            $this->originalPurgeableValues = $originalAttributes;
        }

        return $this->model->attributes = $cleanAttributes;
    }

    /**
     * Returns a collection of fields that will be hashed.
     */
    public function getPurgeableAttributes()
    {
        return $this->model->purgeable;
    }

    /**
     * Returns the original values of any purged attributes.
     */
    public function getOriginalPurgeValues()
    {
        return $this->originalPurgeableValues;
    }

    /**
     * Returns the original values of any purged attributes.
     */
    public function getOriginalPurgeValue($attribute)
    {
        return $this->originalPurgeableValues[$attribute] ?? null;
    }

    /**
     * Restores the original values of any purged attributes.
     */
    public function restorePurgedValues()
    {
        $this->model->attributes = array_merge($this->model->getAttributes(), $this->originalPurgeableValues);
        return $this->model;
    }
}
