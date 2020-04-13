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

        $result = $dongle->parseConcat('concat("#", id, " - ", amount, "(", currency_code, ")")');
        $this->assertEquals('"#" || id || " - " || amount || "(" || currency_code || ")"', $result);

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

        $result = $dongle->parseGroupConcat("group_concat(id separator ')')");
        $this->assertEquals("group_concat(id, ')')", $result);
    }

    public function testPgsqlParseGroupConcat()
    {
        $dongle = new Dongle('pgsql');

        $result = $dongle->parseGroupConcat("group_concat(first_name separator ', ')");
        $this->assertEquals("string_agg(first_name::VARCHAR, ', ')", $result);

        $result = $dongle->parseGroupConcat("group_concat(sometable.first_name SEPARATOR ', ')");
        $this->assertEquals("string_agg(sometable.first_name::VARCHAR, ', ')", $result);

        $result = $dongle->parseGroupConcat("group_concat(id separator ')')");
        $this->assertEquals("string_agg(id::VARCHAR, ')')", $result);
    }

    public function testSqlSrvParseGroupConcat()
    {
        $dongle = new Dongle('sqlsrv');

        $result = $dongle->parseGroupConcat("group_concat(first_name separator ', ')");
        $this->assertEquals("dbo.GROUP_CONCAT_D(first_name, ', ')", $result);

        $result = $dongle->parseGroupConcat("group_concat(sometable.first_name SEPARATOR ', ')");
        $this->assertEquals("dbo.GROUP_CONCAT_D(sometable.first_name, ', ')", $result);

        $result = $dongle->parseGroupConcat("group_concat(id separator ')')");
        $this->assertEquals("dbo.GROUP_CONCAT_D(id, ')')", $result);
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

    public function testSqlSrvParseIfNull()
    {
        $dongle = new Dongle('sqlsrv');

        $result = $dongle->parseIfNull("select ifnull(1,0) from table");
        $this->assertEquals("select isnull(1,0) from table", $result);

        $result = $dongle->parseIfNull("select IFNULL(1,0) from table");
        $this->assertEquals("select isnull(1,0) from table", $result);
    }

    public function testPgSrvParseIfNull()
    {
        $dongle = new Dongle('pgsql');

        $result = $dongle->parseIfNull("select ifnull(1,0) from table");
        $this->assertEquals("select coalesce(1,0) from table", $result);

        $result = $dongle->parseIfNull("select IFNULL(1,0) from table");
        $this->assertEquals("select coalesce(1,0) from table", $result);
    }
}
