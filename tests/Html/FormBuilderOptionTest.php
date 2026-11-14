<?php

use October\Rain\Html\FormBuilder;
use October\Rain\Html\HtmlBuilder;

/**
 * FormBuilderOptionTest
 */
class FormBuilderOptionTest extends TestCase
{
    /**
     * testOptionValueIsEscapedOnce confirms an option value is not encoded twice
     */
    public function testOptionValueIsEscapedOnce()
    {
        $form = (new ReflectionClass(FormBuilder::class))->newInstanceWithoutConstructor();
        $property = new ReflectionProperty(FormBuilder::class, 'html');
        $property->setAccessible(true);
        $property->setValue($form, new HtmlBuilder);

        $html = self::callProtectedMethod($form, 'option', ['A & B', 'A&B', 'A&B']);

        $this->assertSame('<option value="A&amp;B" selected="selected">A &amp; B</option>', (string) $html);
    }
}
