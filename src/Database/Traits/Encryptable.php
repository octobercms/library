<?php namespace October\Rain\Database\Traits;

use App;
use Exception;

trait Encryptable
{
    /**
     * @var array List of attribute names which should be encrypted
     *
     * protected $encryptable = [];
     */

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter Encrypter instance.
     */
    protected $encrypter;

    /**
     * @var array List of original attribute values before they were encrypted.
     */
    protected $originalEncryptableValues = [];

    /**
     * Boot the encryptable trait for a model.
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
                if (in_array($key, $encryptable) && !is_null($value)) {
                    return $model->makeEncryptableValue($key, $value);
                }
            });
            $model->bindEvent('model.beforeGetAttribute', function ($key) use ($model, $encryptable) {
                if (in_array($key, $encryptable) && array_get($model->attributes, $key) != null) {
                    return $model->getEncryptableValue($key);
                }
            });
        });
    }

    /**
     * Encrypts an attribute value and saves it in the original locker.
     * @param  string $key   Attribute
     * @param  string $value Value to encrypt
     * @return string        Encrypted value
     */
    public function makeEncryptableValue($key, $value)
    {
        $this->originalEncryptableValues[$key] = $value;
        return $this->getEncrypter()->encrypt($value);
    }

    /**
     * Decrypts an attribute value
     * @param  string $key Attribute
     * @return string      Decrypted value
     */
    public function getEncryptableValue($key)
    {
        return $this->getEncrypter()->decrypt($this->attributes[$key]);
    }

    /**
     * Returns a collection of fields that will be encrypted.
     * @return array
     */
    public function getEncryptableAttributes()
    {
        return $this->encryptable;
    }

    /**
     * Returns the original values of any encrypted attributes.
     * @return array
     */
    public function getOriginalEncryptableValues()
    {
        return $this->originalEncryptableValues;
    }

    /**
     * Returns the original values of any encrypted attributes.
     * @return mixed
     */
    public function getOriginalEncryptableValue($attribute)
    {
        return $this->originalEncryptableValues[$attribute] ?? null;
    }

    /**
     * Provides the encrypter instance.
     *
     * @return \Illuminate\Contracts\Encryption\Encrypter
     */
    public function getEncrypter()
    {
        return (!is_null($this->encrypter)) ? $this->encrypter : App::make('encrypter');
    }

    /**
     * Sets the encrypter instance.
     *
     * @param \Illuminate\Contracts\Encryption\Encrypter $encrypter
     * @return void
     */
    public function setEncrypter(\Illuminate\Contracts\Encryption\Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }
}
