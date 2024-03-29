<?php

class ValidationTest extends TestCase
{
    use \October\Rain\Database\Traits\Validation;

    public $exists;

    public $id = 20;

    public function testUniqueRule()
    {
        // Basic usage of unique rule
        $rules = ['name' => 'unique', 'email' => 'unique:users'];

        $this->exists = true;
        $this->assertEquals([
            'name' => ['unique:users,name,7,the_id'],
            'email' => ['unique:users,email,7,the_id']
        ], $this->processValidationRules($rules));

        $this->exists = false;
        $this->assertEquals([
            'name' => ['unique:users'],
            'email' => ['unique:users']
        ], $this->processValidationRules($rules));

        // Custom database connection
        $rules = ['email' => 'unique:myconnection.users'];

        $this->exists = true;
        $this->assertEquals([
            'email' => ['unique:myconnection.users,email,7,the_id']
        ], $this->processValidationRules($rules));

        $this->exists = false;
        $this->assertEquals([
            'email' => ['unique:myconnection.users']
        ], $this->processValidationRules($rules));

        // Custom table column name
        $rules = ['email' => 'unique:users,email_address'];

        $this->exists = true;
        $this->assertEquals([
            'email' => ['unique:users,email_address,7,the_id']
        ], $this->processValidationRules($rules));

        $this->exists = false;
        $this->assertEquals([
            'email' => ['unique:users,email_address']
        ], $this->processValidationRules($rules));

        // Forcing a unique rule to ignore a given ID
        $rules = ['email' => 'unique:users,email_address,10'];

        $this->exists = true;
        $this->assertEquals([
            'email' => ['unique:users,email_address,7,the_id']
        ], $this->processValidationRules($rules));

        $this->exists = false;
        $this->assertEquals([
            'email' => ['unique:users,email_address,10']
        ], $this->processValidationRules($rules));

        // Adding additional where clauses
        $rules = ['email' => 'unique:users,email_address,NULL,id,account_id,1'];

        $this->exists = true;
        $this->assertEquals([
            'email' => ['unique:users,email_address,20,id,account_id,1']
        ], $this->processValidationRules($rules));

        $this->exists = false;
        $this->assertEquals([
            'email' => ['unique:users,email_address,NULL,id,account_id,1']
        ], $this->processValidationRules($rules));

        // Adding multiple additional where clauses
        $rules = ['email' => 'unique:users,email_address,NULL,id,account_id,1,account_name,"Foo",user_id,3'];

        $this->exists = true;
        $this->assertEquals([
            'email' => ['unique:users,email_address,20,id,account_id,1,account_name,"Foo",user_id,3']
        ], $this->processValidationRules($rules));

        $this->exists = false;
        $this->assertEquals([
            'email' => ['unique:users,email_address,NULL,id,account_id,1,account_name,"Foo",user_id,3']
        ], $this->processValidationRules($rules));
    }

    protected function getTable()
    {
        return 'users';
    }

    protected function getConnectionName()
    {
        return 'mysql';
    }

    protected function getKey()
    {
        return 7;
    }

    protected function getKeyName()
    {
        return 'the_id';
    }

    public function testArrayFieldNames()
    {
        $mock = $this->getMockForTrait(\October\Rain\Database\Traits\Validation::class);

        $rules = [
            'field' => 'required',
            'field.two' => 'required|boolean',
            'field[three]' => 'required|date',
            'field[three][child]' => 'required',
            'field[four][][name]' => 'required',
            'field[five' => 'required|string',
            'field][six' => 'required|string',
            'field]seven' => 'required|string',
        ];
        $rules = self::callProtectedMethod($mock, 'processRuleFieldNames', [$rules]);

        $this->assertEquals([
            'field' => 'required',
            'field.two' => 'required|boolean',
            'field.three' => 'required|date',
            'field.three.child' => 'required',
            'field.four.*.name' => 'required',
            'field[five' => 'required|string',
            'field][six' => 'required|string',
            'field]seven' => 'required|string',
        ], $rules);
    }
}
