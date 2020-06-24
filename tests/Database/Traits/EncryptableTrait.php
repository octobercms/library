<?php

class EncryptableTest extends TestCase
{
    public function setUp()
    {
        $capsule = new Illuminate\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $capsule->schema()->create('secrets', function ($table) {
            $table->increments('id');
            $table->string('secret');
            $table->timestamps();
        });
    }

    public function testEncryptsZeroValues()
    {
        $testModel = TestModelEncryptable::create(['secret' => 'test']);
        $this->assertEquals($testModel->attributes['secret'], 'test_encrypted');

        $testModel->secret = '';
        $this->assertEquals($testModel->attributes['secret'], '_encrypted');

        $testModel->secret = 0;
        $this->assertEquals($testModel->attributes['secret'], '0_encrypted');

        $testModel->secret = null;
        $this->assertEquals($testModel->attributes['secret'], null);
    }
}

class TestModelEncryptable extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Encryptable;

    protected $encryptable = ['secret'];
    protected $fillable = ['secret'];
    protected $table = 'secrets';
}

/**
 * Fake implementation of the Crypt facade
 * that 'encrypts' and 'decrypts' the provided values.
 * Used internally in the Encryptable trait.
 */
class Crypt
{
    public static function encrypt($value)
    {
        return $value.'_encrypted';
    }

    public static function decrypt($value)
    {
        return substr($value, 0, strpos($value, '_encrypted'));
    }
}
