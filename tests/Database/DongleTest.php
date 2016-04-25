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

    public function testSqliteParseBooleanExpression()
    {
        $dongle = new Dongle('sqlite');

        $result = $dongle->parseBooleanExpression("select * from table where is_true = true");
        $this->assertEquals("select * from table where is_true = 1", $result);

        $result = $dongle->parseBooleanExpression("is_true = true and is_false <> true");
        $this->assertEquals("is_true = 1 and is_false <> 1", $result);

        $result = $dongle->parseBooleanExpression("is_true = true and is_false = false or is_whatever = 2");
        $this->assertEquals("is_true = 1 and is_false = 0 or is_whatever = 2", $result);

        $result = $dongle->parseBooleanExpression("select * from table where is_true = true");
        $this->assertEquals("select * from table where is_true = 1", $result);
    }
}