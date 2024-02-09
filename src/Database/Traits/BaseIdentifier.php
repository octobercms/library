<?php namespace October\Rain\Database\Traits;

/**
 * BaseIdentifier trait adds random base64 identifiers to a model used as a random
 * lookup key that is immune to enumeration attacks. The model is assumed to have
 * the attribute: baseid.
 *
 * Add this to your database table with:
 *
 *     $table->string('baseid')->nullable()->index();
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait BaseIdentifier
{
    /**
     * initializeBaseIdentifier trait for a model.
     */
    public function initializeBaseIdentifier()
    {
        $this->bindEvent('model.saveInternal', function () {
            $this->baseIdentifyAttributes();
        });
    }

    /**
     * baseIdentifyAttributes
     */
    public function baseIdentifyAttributes()
    {
        $baseidAttribute = $this->getBaseIdentifierColumnName();
        if (!$this->{$baseidAttribute}) {
            $this->attributes[$baseidAttribute] = $this->getBaseIdentifierUniqueAttributeValue($baseidAttribute);
        }
    }

    /**
     * generateBaseIdentifier returns a random encoded 64 bit number
     */
    public function generateBaseIdentifier()
    {
        return rtrim(strtr(base64_encode(random_bytes(8)), '+/', '-_'), '=');
    }

    /**
     * getBaseIdentifierUniqueAttributeValue ensures a unique attribute value, if the value
     * is already used another base identifier is created. Returns a safe value that is unique.
     * @param string $name
     * @return string
     */
    protected function getBaseIdentifierUniqueAttributeValue($name)
    {
        $value = $this->generateBaseIdentifier();

        while ($this->newQueryWithoutScopes()->where($name, $value)->count() > 0) {
            $value = $this->generateBaseIdentifier();
        }

        return $value;
    }

    /**
     * getBaseIdentifierColumnName gets the name of the "baseid" column.
     * @return string
     */
    public function getBaseIdentifierColumnName()
    {
        return defined('static::BASEID') ? static::BASEID : 'baseid';
    }
}
