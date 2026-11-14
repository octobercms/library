<?php

use Illuminate\Filesystem\Filesystem;
use October\Rain\Translation\FileLoader;
use October\Rain\Translation\Translator;
use October\Rain\Validation\Validator;

/**
 * OrdinalPositionTest
 */
class OrdinalPositionTest extends TestCase
{
    /**
     * testOrdinalPositionPlaceholderIsReplaced confirms the placeholder resolves to the item position
     */
    public function testOrdinalPositionPlaceholderIsReplaced()
    {
        $translator = new Translator(new FileLoader(new Filesystem, __DIR__ . '/../fixtures/lang'), 'en');

        $validator = new Validator(
            $translator,
            ['items' => [['name' => 'ok'], ['name' => '']]],
            ['items.*.name' => 'required'],
            ['items.*.name.required' => 'The :ordinal-position item needs a name.']
        );

        $this->assertTrue($validator->fails());
        $this->assertStringNotContainsString(':ordinal-position', $validator->errors()->first('items.1.name'));
    }
}
