<?php namespace October\Rain\Database\Traits;

use October\Rain\Support\Str;

trait Sluggable
{
    /**
     * @var array List of attributes to automatically generate unique URL names (slugs) for.
     */
    protected $slugs = [];

    /**
     * Adds slug attributes to the dataset, used before saving.
     * @return void
     */
    public function slugAttributes()
    {
        foreach ($this->slugs as $slugAttribute => $sourceAttributes)
            $this->setSluggedValue($slugAttribute, $sourceAttributes);
    }

    /**
     * Sets a single slug attribute value.
     * @param string $slugAttribute Attribute to populate with the slug.
     * @param mixed $sourceAttributes Attribute(s) to generate the slug from.
     * Supports dotted notation for relations.
     * @param int $maxLength Maximum length for the slug not including the counter.
     * @return string The generated value.
     */
    public function setSluggedValue($slugAttribute, $sourceAttributes, $maxLength = 240)
    {
        if (!isset($this->{$slugAttribute})) {
            if (!is_array($sourceAttributes))
                $sourceAttributes = [$sourceAttributes];

            $slugArr = [];
            foreach ($sourceAttributes as $attribute) {
                $slugArr[] = $this->getAttribute($attribute);
            }

            $slug = implode(' ', $slugArr);
            $slug = substr($slug, 0, $maxLength);
            $slug = Str::slug($slug);
        }
        else {
            $slug = $this->{$slugAttribute};
        }

        return $this->{$slugAttribute} = $this->getUniqueAttributeValue($slugAttribute, $slug);
    }

    /**
     * Ensures a unique attribute value, if the value is already used a counter suffix is added.
     * @param string $name The database column name.
     * @param value $value The desired column value.
     * @return string A safe value that is unique.
     */
    public function getUniqueAttributeValue($name, $value)
    {
        $counter = 1;
        $separator = '-';

        // Remove any existing suffixes
        $_value = preg_replace('/'.preg_quote($separator).'[0-9]+$/', '', trim($value));

        while ($this->newQuery()->where($name, $_value)->count() > 0) {
            $counter++;
            $_value = $value . $separator . $counter;
        }

        return $_value;
    }

}