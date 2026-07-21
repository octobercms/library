<?php

class ValidationTest extends TestCase
{
    use \October\Rain\Database\Traits\Validation;

    public $exists;

    public $id = 20;

    public $rules = [];

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

    public function testRemoveValidationRuleByString()
    {
        $this->rules = [
            'name' => ['required', 'string'],
            'email' => ['required', 'unique:users', 'email'],
        ];

        $this->removeValidationRule('name', 'required');
        $this->assertEquals(['string'], $this->rules['name']);

        // Prefix match should strip parameterized rule
        $this->removeValidationRule('email', 'unique');
        $this->assertEquals(['required', 'email'], $this->rules['email']);
    }

    public function testRemoveValidationRuleWildcard()
    {
        $this->rules = [
            'name' => ['required'],
            'email' => ['required', 'email'],
        ];

        $this->removeValidationRule('email');
        $this->assertArrayNotHasKey('email', $this->rules);
        $this->assertEquals(['required'], $this->rules['name']);
    }

    public function testRemoveValidationRuleFromPipeString()
    {
        $this->rules = [
            'email' => 'required|unique:users|email',
        ];

        $this->removeValidationRule('email', 'unique');
        $this->assertEquals(['required', 'email'], $this->rules['email']);
    }

    public function testRemoveValidationRuleByObjectInstance()
    {
        $unique = new \Illuminate\Validation\Rules\Unique('users');

        $this->rules = [
            'email' => ['required', $unique, 'email'],
        ];

        $this->removeValidationRule('email', $unique);
        $this->assertEquals(['required', 'email'], $this->rules['email']);
    }

    public function testRemoveValidationRuleByClassName()
    {
        $this->rules = [
            'email' => [
                'required',
                new \Illuminate\Validation\Rules\Unique('users'),
                'email',
            ],
        ];

        $this->removeValidationRule('email', \Illuminate\Validation\Rules\Unique::class);
        $this->assertEquals(['required', 'email'], $this->rules['email']);
    }

    public function testRemoveValidationRuleReindexesArray()
    {
        $this->rules = [
            'email' => ['required', 'unique:users', 'email'],
        ];

        $this->removeValidationRule('email', 'unique');
        $this->assertEquals([0, 1], array_keys($this->rules['email']));
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
