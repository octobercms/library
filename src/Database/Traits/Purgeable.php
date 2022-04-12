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
     * @var array List of attribute names which should not be saved to the database.
     *
     * protected $purgeable = [];
     */

    /**
     * @var array List of original attribute values before they were purged.
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
                get_class($this)
            ));
        }

        // Remove any purge attributes from the data set
        $this->bindEvent('model.saveInternal', function () {
            $this->purgeAttributes();
        });
    }

    /**
     * Adds an attribute to the purgeable attributes list
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addPurgeable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->purgeable = array_merge($this->purgeable, $attributes);
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

        $attributes = $this->getAttributes();
        $cleanAttributes = array_diff_key($attributes, array_flip($purgeable));
        $originalAttributes = array_diff_key($attributes, $cleanAttributes);

        if (is_array($this->originalPurgeableValues)) {
            $this->originalPurgeableValues = array_merge($this->originalPurgeableValues, $originalAttributes);
        }
        else {
            $this->originalPurgeableValues = $originalAttributes;
        }

        return $this->attributes = $cleanAttributes;
    }

    /**
     * Returns a collection of fields that will be hashed.
     */
    public function getPurgeableAttributes()
    {
        return $this->purgeable;
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
        $this->attributes = array_merge($this->getAttributes(), $this->originalPurgeableValues);
    }
}
