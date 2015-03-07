<?php

use October\Rain\Support\Str;

class StrTest extends TestCase
{

    public function testEvalBoolean()
    {
        $result = Str::evalBoolean('yes');
        $this->assertTrue($result);

        $result = Str::evalBoolean('y');
        $this->assertTrue($result);

        $result = Str::evalBoolean('true');
        $this->assertTrue($result);

        $result = Str::evalBoolean('no');
        $this->assertFalse($result);

        $result = Str::evalBoolean('n');
        $this->assertFalse($result);

        $result = Str::evalBoolean('false');
        $this->assertFalse($result);

        $result = Str::evalBoolean('nothing to see here');
        $this->assertEquals('nothing to see here', $result);
    }

}