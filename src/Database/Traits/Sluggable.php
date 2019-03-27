<?php namespace October\Rain\Database\Traits;

use October\Rain\Support\Str;
use Exception;

trait Sluggable
{

    /**
     * @var array List of attributes to automatically generate unique URL names (slugs) for.
     *
     * protected $slugs = [];
     */

    /**
     * @var bool Allow trashed slugs to be counted in the slug generation.
     *
     * protected $allowTrashedSlugs = false;
     */

    /**
     * Boot the sluggable trait for a model.
     * @return void
     */
    public static function bootSluggable()
    {
        if (!property_exists(get_called_class(), 'slugs')) {
            throw new Exception(sprintf(
                'You must define a $slugs property in %s to use the Sluggable trait.', get_called_class()
            ));
        }

        /*
         * Set slugged attributes on new records and existing records if slug is missing.
         */
        static::extend(function($model) {
            $model->bindEvent('model.saveInternal', function() use ($model) {
                $model->slugAttributes();
            });
        });
    }

    /**
     * Adds slug attributes to the dataset, used before saving.
     * @return void
     */
    public function slugAttributes()
    {
        foreach ($this->slugs as $slugAttribute => $sourceAttributes) {
            $this->setSluggedValue($slugAttribute, $sourceAttributes);
        }
    }

    /**
     * Sets a single slug attribute value.
     * @param string $slugAttribute Attribute to populate with the slug.
     * @param mixed $sourceAttributes Attribute(s) to generate the slug from.
     * Supports dotted notation for relations.
     * @param int $maxLength Maximum length for the slug not including the counter.
     * @return string The generated value.
     */
    public function setSluggedValue($slugAttribute, $sourceAttributes, $maxLength = 175)
    {
        if (!isset($this->{$slugAttribute}) || !mb_strlen($this->{$slugAttribute})) {
            if (!is_array($sourceAttributes)) {
                $sourceAttributes = [$sourceAttributes];
            }

            $slugArr = [];
            foreach ($sourceAttributes as $attribute) {
                $slugArr[] = $this->getSluggableSourceAttributeValue($attribute);
            }

            $slug = implode(' ', $slugArr);
            $slug = mb_substr($slug, 0, $maxLength);
            $slug = Str::slug($slug, $this->getSluggableSeparator());
        }
        else {
            $slug = $this->{$slugAttribute};
        }

        return $this->{$slugAttribute} = $this->getSluggableUniqueAttributeValue($slugAttribute, $slug);
    }

    /**
     * Ensures a unique attribute value, if the value is already used a counter suffix is added.
     * @param string $name The database column name.
     * @param value $value The desired column value.
     * @return string A safe value that is unique.
     */
    protected function getSluggableUniqueAttributeValue($name, $value)
    {
        $counter = 1;
        $separator = $this->getSluggableSeparator();
        $_value = $value;
        while (($this->methodExists('withTrashed') && $this->allowTrashedSlugs) ?
            $this->newSluggableQuery()->where($name, $_value)->withTrashed()->count() > 0 :
            $this->newSluggableQuery()->where($name, $_value)->count() > 0
        ) {
            $counter++;
            $_value = $value . $separator . $counter;
        }

        return $_value;
    }

    /**
     * Returns a query that excludes the current record if it exists
     * @return Builder
     */
    protected function newSluggableQuery()
    {
        return $this->exists
            ? $this->newQuery()->where($this->getKeyName(), '<>', $this->getKey())
            : $this->newQuery();
    }

    /**
     * Get an attribute relation value using dotted notation.
     * Eg: author.name
     * @return mixed
     */
    protected function getSluggableSourceAttributeValue($key)
    {
        if (strpos($key, '.') === false) {
            return $this->getAttribute($key);
        }

        $keyParts = explode('.', $key);
        $value = $this;
        foreach ($keyParts as $part) {
            if (!isset($value[$part])) {
                return null;
            }

            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Override the default slug separator.
     * @return string
     */
    public function getSluggableSeparator()
    {
        return defined('static::SLUG_SEPARATOR') ? static::SLUG_SEPARATOR : '-';
    }

}
