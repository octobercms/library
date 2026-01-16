<?php namespace October\Rain\Database\Traits;

use Crypt;
use Exception;

/**
 * Encryptable database trait
 *
 * @deprecated Use Laravel's built-in 'encrypted' cast instead:
 *             protected $casts = ['secret' => 'encrypted'];
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Encryptable
{
    /**
     * @var array encryptable is a list of attribute names which should be encrypted
     *
     * protected $encryptable = [];
     */

    /**
     * @var array originalEncryptableValues is the original attribute values
     * before they were encrypted
     */
    protected $originalEncryptableValues = [];

    /**
     * initializeEncryptable trait for a model
     */
    public function initializeEncryptable()
    {
        if (!is_array($this->encryptable)) {
            throw new Exception(sprintf(
                'The $encryptable property in %s must be an array to use the Encryptable trait.',
                static::class
            ));
        }
    }

    /**
     * setAttribute overrides the base method to encrypt encryptable attributes.
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        if (
            in_array($key, $this->getEncryptableAttributes()) &&
            $value !== null &&
            $value !== ''
        ) {
            $value = $this->makeEncryptableValue($key, $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * getAttributeValue overrides the base method to decrypt encryptable attributes.
     * @param string $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        if (
            in_array($key, $this->getEncryptableAttributes()) &&
            array_get($this->attributes, $key) !== null &&
            array_get($this->attributes, $key) !== ''
        ) {
            return $this->getEncryptableValue($key);
        }

        return parent::getAttributeValue($key);
    }

    /**
     * makeEncryptableValue encrypts an attribute value and saves it in the original locker
     * @param  string $key   Attribute
     * @param  string $value Value to encrypt
     * @return string Encrypted value
     */
    public function makeEncryptableValue($key, $value)
    {
        $this->originalEncryptableValues[$key] = $value;

        return Crypt::encrypt($value);
    }

    /**
     * getEncryptableValue decrypts an attribute value
     * @param  string $key Attribute
     * @return string Decrypted value
     */
    public function getEncryptableValue($key)
    {
        return Crypt::decrypt($this->attributes[$key]);
    }

    /**
     * getEncryptableAttributes returns a collection of fields that will be encrypted.
     * @return array
     */
    public function getEncryptableAttributes()
    {
        return $this->encryptable;
    }

    /**
     * getOriginalEncryptableValues returns the original values of any encrypted attributes
     * @return array
     */
    public function getOriginalEncryptableValues()
    {
        return $this->originalEncryptableValues;
    }

    /**
     * getOriginalEncryptableValue returns the original values of any encrypted attributes.
     * @return mixed
     */
    public function getOriginalEncryptableValue($attribute)
    {
        return $this->originalEncryptableValues[$attribute] ?? null;
    }
}
