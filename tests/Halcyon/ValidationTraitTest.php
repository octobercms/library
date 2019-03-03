<?php
class ValidationTraitTest extends TestCase
{
    public function testArrayFieldNames()
    {
        $mock = $this->getMockForTrait('October\Rain\Halcyon\Traits\Validation');

        $rules = [
            'field' => 'required',
            'field.two' => 'required|boolean',
            'field[three]' => 'required|date',
            'field[three][child]' => 'required'
        ];
        $rules = self::callProtectedMethod($mock, 'processRuleFieldNames', [$rules]);

        $this->assertEquals([
            'field' => 'required',
            'field.two' => 'required|boolean',
            'field.three' => 'required|date',
            'field.three.child' => 'required'
        ], $rules);
    }
}
