<?php namespace October\Rain\Database\Traits;

use Crypt;
use Exception;

/**
 * Encryptable database trait
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
     * bootEncryptable boots the encryptable trait for a model
     * @return void
     */
    public static function bootEncryptable()
    {
        if (!property_exists(get_called_class(), 'encryptable')) {
            throw new Exception(sprintf(
                'You must define a $encryptable property in %s to use the Encryptable trait.',
                get_called_class()
            ));
        }

        /*
         * Encrypt required fields when necessary
         */
        static::extend(function ($model) {
            $encryptable = $model->getEncryptableAttributes();

            $model->bindEvent('model.beforeSetAttribute', function ($key, $value) use ($model, $encryptable) {
                if (
                    in_array($key, $encryptable) &&
                    $value !== null &&
                    $value !== ''
                ) {
                    return $model->makeEncryptableValue($key, $value);
                }
            });

            $model->bindEvent('model.beforeGetAttribute', function ($key) use ($model, $encryptable) {
                if (
                    in_array($key, $encryptable) &&
                    array_get($model->attributes, $key) !== null &&
                    array_get($model->attributes, $key) !== ''
                ) {
                    return $model->getEncryptableValue($key);
                }
            });
        });
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
