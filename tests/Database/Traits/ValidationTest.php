<?php

class ValidationTest extends TestCase
{
    use \October\Rain\Database\Traits\Validation;

    public $exists;

    public function testUnique()
    {
        $rules          = ['column' => 'unique:table,column,key,id,where,5'];
        $processedRules = ['column' => ['unique:table,column,key,id,where,5']];

        $this->exists = false;
        $this->assertEquals($processedRules, $this->processValidationRules($rules));

        $this->exists = true;
        $this->assertEquals($processedRules, $this->processValidationRules($rules));
    }

    protected function getTable()
    {
        return 'table';
    }

    protected function getKey()
    {
        return 'key';
    }
}