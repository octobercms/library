<?php

use October\Rain\Database\Dongle;

class DongleTest extends TestCase
{
    public function testSqliteParseConcat()
    {
        $dongle = new Dongle('sqlite');

        $result = $dongle->parseConcat("concat(first_name, ' ', last_name)");
        $this->assertEquals("first_name || ' ' || last_name", $result);

        $result = $dongle->parseConcat("CONCAT(  first_name   , ' ',    last_name  )");
        $this->assertEquals("first_name || ' ' || last_name", $result);

        $result = $dongle->parseConcat("group_concat(first_name, ' ', last_name)");
        $this->assertEquals("group_concat(first_name, ' ', last_name)", $result);
    }

    public function testSqliteParseGroupConcat()
    {
        $dongle = new Dongle('sqlite');

        $result = $dongle->parseGroupConcat("group_concat(first_name separator ', ')");
        $this->assertEquals("group_concat(first_name, ', ')", $result);

        $result = $dongle->parseGroupConcat("group_concat(sometable.first_name SEPARATOR ', ')");
        $this->assertEquals("group_concat(sometable.first_name, ', ')", $result);
    }
}