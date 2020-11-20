<?php

use Illuminate\Encryption\Encrypter;

class EncryptableTest extends DbTestCase
{
    const TEST_CRYPT_KEY = 'gBmM1S5bxZ5ePRj5';

    /**
     * @var \Illuminate\Encryption\Encrypter Encrypter instance.
     */
    protected $encrypter;

    public function setUp()
    {
        parent::setUp();
        $this->createTable();

        $this->encrypter = new Encrypter(self::TEST_CRYPT_KEY, 'AES-128-CBC');
    }

    public function testEncryptableTrait()
    {
        $testModel = new TestModelEncryptable();
        $testModel->setEncrypter($this->encrypter);

        $testModel->fill(['secret' => 'test']);
        $this->assertEquals('test', $testModel->secret);
        $this->assertNotEquals('test', $testModel->attributes['secret']);
        $payloadOne = json_decode(base64_decode($testModel->attributes['secret']), true);
        $this->assertEquals(['iv', 'value', 'mac'], array_keys($payloadOne));

        $testModel->secret = '';
        $this->assertEquals('', $testModel->secret);
        $this->assertNotEquals('', $testModel->attributes['secret']);
        $payloadTwo = json_decode(base64_decode($testModel->attributes['secret']), true);
        $this->assertEquals(['iv', 'value', 'mac'], array_keys($payloadTwo));
        $this->assertNotEquals($payloadOne['value'], $payloadTwo['value']);

        $testModel->secret = 0;
        $this->assertEquals(0, $testModel->secret);
        $this->assertNotEquals(0, $testModel->attributes['secret']);
        $payloadThree = json_decode(base64_decode($testModel->attributes['secret']), true);
        $this->assertEquals(['iv', 'value', 'mac'], array_keys($payloadThree));
        $this->assertNotEquals($payloadTwo['value'], $payloadThree['value']);

        $testModel->secret = null;
        $this->assertNull($testModel->secret);
        $this->assertNull($testModel->attributes['secret']);
    }

    protected function createTable()
    {
        $this->db->schema()->create('secrets', function ($table) {
            $table->increments('id');
            $table->string('secret');
            $table->timestamps();
        });
    }
}

class TestModelEncryptable extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Encryptable;

    protected $encryptable = ['secret'];
    protected $fillable = ['secret'];
    protected $table = 'secrets';
}
