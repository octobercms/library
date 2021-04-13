<?php

use Illuminate\Encryption\Encrypter;

class EncryptableTest extends TestCase
{
    const TEST_CRYPT_KEY = 'gBmM1S5bxZ5ePRj5';

    public function setUp(): void
    {
        parent::setUp();

        Crypt::swap(new Encrypter(self::TEST_CRYPT_KEY, 'AES-128-CBC'));
    }

    public function testEncryptableTrait()
    {
        $testModel = new TestModelEncryptable;

        // Regular encrypt
        $testModel->fill(['secret' => 'test']);
        $this->assertEquals('test', $testModel->secret);
        $this->assertNotEquals('test', $testModel->attributes['secret']);
        $payloadOne = json_decode(base64_decode($testModel->attributes['secret']), true);
        $this->assertEquals(['iv', 'value', 'mac'], array_keys($payloadOne));

        // Don't encrypt empty strings
        $testModel->secret = '';
        $this->assertEquals('', $testModel->secret);
        $this->assertEquals('', $testModel->attributes['secret']);

        // Encrypt numerics
        $testModel->secret = 0;
        $this->assertEquals(0, $testModel->secret);
        $this->assertNotEquals(0, $testModel->attributes['secret']);
        $payloadTwo = json_decode(base64_decode($testModel->attributes['secret']), true);
        $this->assertEquals(['iv', 'value', 'mac'], array_keys($payloadTwo));
        $this->assertNotEquals($payloadOne['value'], $payloadTwo['value']);

        // Test reset
        $testModel->secret = null;
        $this->assertNull($testModel->secret);
        $this->assertNull($testModel->attributes['secret']);
    }
}

class TestModelEncryptable extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Encryptable;

    protected $encryptable = ['secret'];
    protected $fillable = ['secret'];
    protected $table = 'secrets';
}
