<?php
class EmailValidationTest extends TestCase
{
    // This validation should fail, as per Laravel pre-5.8, as well as current expected October functionality.
    public function testDefaultFilter()
    {
        $validator = $this->app['validator']->make([
            'email' => 'test@localhost',
        ], [
            'email' => 'email'
        ]);

        $this->assertTrue($validator->fails());

        $validator = $this->app['validator']->make([
            'email' => '$šte/-{st+email#@example.com',
        ], [
            'email' => 'email'
        ]);

        $this->assertTrue($validator->fails());
    }

    // If people wish to use the new RFC-2822-compliant(?) validation, they can still do so by adding a suffix to the
    // email validation rule.

    // NOTE: It's possible this test may fail at a future point, when egulias/EmailValidator gets its act together.
    public function testRFCFilter()
    {
        $validator = $this->app['validator']->make([
            'email' => 'test@localhost',
        ], [
            'email' => 'email:rfc'
        ]);

        $this->assertFalse($validator->fails());

        $validator = $this->app['validator']->make([
            'email' => '$šte/-{st+email#@example.com',
        ], [
            'email' => 'email:rfc'
        ]);

        $this->assertFalse($validator->fails());
    }
}
