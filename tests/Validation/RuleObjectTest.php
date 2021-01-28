<?php

use Illuminate\Filesystem\Filesystem;
use October\Rain\Translation\FileLoader;
use October\Rain\Translation\Translator;
use October\Rain\Validation\Factory;

class RuleObjectTest extends TestCase
{
    /**
     * @var Factory
     */
    protected $validation;

    /**
     * @var Translator
     */
    protected $translator;

    public function setUp(): void
    {
        include_once __DIR__ . '/../fixtures/validation/FailRule.php';
        include_once __DIR__ . '/../fixtures/validation/PassRule.php';
        include_once __DIR__ . '/../fixtures/validation/TranslatedFailRule.php';

        parent::setUp();

        $path       = __DIR__ . '/../fixtures/lang';
        $fileLoader = new FileLoader(new Filesystem(), $path);
        $translator = new Translator($fileLoader, 'en');
        $this->translator = $translator;

        $this->validation = new Factory($this->translator, null);
    }

    public function testRuleObject()
    {
        $validator = $this->validation->make([
            'test' => 'value',
        ], [
            'test' => new FailRule
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('Fallback message', $validator->errors()->first('test'));
    }

    public function testRuleObjectPasses()
    {
        $validator = $this->validation->make([
            'test' => 'value',
        ], [
            'test' => new PassRule
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testRuleObjectTranslatedMessage()
    {
        $validator = $this->validation->make([
            'test' => 'value',
        ], [
            'test' => new TranslatedFailRule
        ]);

        $this->assertTrue($validator->fails());
        $this->assertEquals('Translated fallback message', $validator->errors()->first('test'));
    }
}
