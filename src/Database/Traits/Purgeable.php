<?php namespace October\Rain\Database\Traits;

use Exception;

/**
 * Purgeable
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Purgeable
{
    /**
     * @var array purgeable attribute names which should not be saved to the database.
     *
     * protected $purgeable = [];
     */

    /**
     * @var array originalPurgeableValues before they were purged.
     */
    protected $originalPurgeableValues = [];

    /**
     * initializePurgeable trait for a model.
     */
    public function initializePurgeable()
    {
        if (!is_array($this->purgeable)) {
            throw new Exception(sprintf(
                'The $purgeable property in %s must be an array to use the Purgeable trait.',
                static::class
            ));
        }

        $this->bindEvent('model.beforeSaveDone', [$this, 'purgeAttributes']);
    }

    /**
     * addPurgeable adds an attribute to the purgeable attributes list
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addPurgeable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->purgeable = array_merge($this->purgeable, $attributes);
    }

    /**
     * purgeAttributes removes purged attributes from the dataset, used before saving.
     * Specify attributesToPurge, if unspecified, $purgeable property is used
     * @param mixed $attributes
     * @return array
     */
    public function purgeAttributes($attributesToPurge = null)
    {
        if ($attributesToPurge === null) {
            $purgeable = $this->getPurgeableAttributes();
        }
        else {
            $purgeable = (array) $attributesToPurge;
        }

        $attributes = $this->getAttributes();

        $cleanAttributes = array_diff_key($attributes, array_flip($purgeable));

        $originalAttributes = array_diff_key($attributes, $cleanAttributes);

        $this->originalPurgeableValues = array_merge(
            $this->originalPurgeableValues,
            $originalAttributes
        );

        return $this->attributes = $cleanAttributes;
    }

    /**
     * getPurgeableAttributes returns a collection of fields that will be hashed.
     */
    public function getPurgeableAttributes()
    {
        return $this->purgeable;
    }

    /**
     * getOriginalPurgeValues returns the original values of any purged attributes.
     */
    public function getOriginalPurgeValues()
    {
        return $this->originalPurgeableValues;
    }

    /**
     * getOriginalPurgeValue returns the original values of any purged attributes.
     */
    public function getOriginalPurgeValue($attribute)
    {
        return $this->attributes[$attribute]
            ?? ($this->originalPurgeableValues[$attribute] ?? null);
    }

    /**
     * restorePurgedValues restores the original values of any purged attributes.
     */
    public function restorePurgedValues()
    {
        $this->attributes = array_merge(
            $this->getAttributes(),
            $this->originalPurgeableValues
        );
    }
}
