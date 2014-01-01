<?php

use October\Rain\Config\Write;

class WriteTest extends TestCase
{

    public function testWriteTo()
    {
        $writer = new Write;

        /*
         * Rewrite a single level string
         */
        $contents = file_get_contents(__DIR__ . '../../fixtures/Config/sample-config.php');
        $contents = $writer->to($contents, ['url' => 'http://octobercms.com']);
        $result = eval('?>'.$contents);

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('http://octobercms.com', $result['url']);

        /*
         * Rewrite a second level string
         */
        $contents = $writer->to($contents, ['memcached.host' => '69.69.69.69']);
        $result = eval('?>'.$contents);

        $this->assertArrayHasKey('memcached', $result);
        $this->assertArrayHasKey('host', $result['memcached']);
        $this->assertEquals('69.69.69.69', $result['memcached']['host']);

        /*
         * Rewrite a third level string
         */
        $contents = $writer->to($contents, ['connections.mysql.host' => '127.0.0.1']);
        $result = eval('?>'.$contents);

        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('mysql', $result['connections']);
        $this->assertArrayHasKey('host', $result['connections']['mysql']);
        $this->assertEquals('127.0.0.1', $result['connections']['mysql']['host']);

        /*
         * Test alternative quoting
         */
        $contents = $writer->to($contents, ['timezone' => 'The Fifth Dimension']);
        $contents = $writer->to($contents, ['timezoneAgain' => 'The "Sixth" Dimension']);
        $result = eval('?>'.$contents);

        $this->assertArrayHasKey('timezone', $result);
        $this->assertArrayHasKey('timezoneAgain', $result);
        $this->assertEquals('The Fifth Dimension', $result['timezone']);
        $this->assertEquals('The "Sixth" Dimension', $result['timezoneAgain']);

        /*
         * Rewrite a boolean
         */
        $contents = $writer->to($contents, ['debug' => false]);
        $contents = $writer->to($contents, ['debugAgain' => true]);
        $contents = $writer->to($contents, ['bullyIan' => true]);
        $contents = $writer->to($contents, ['booLeeIan' => false]);
        $contents = $writer->to($contents, ['memcached.weight' => false]);
        $contents = $writer->to($contents, ['connections.pgsql.password' => true]);
        $result = eval('?>'.$contents);

        $this->assertArrayHasKey('debug', $result);
        $this->assertArrayHasKey('debugAgain', $result);
        $this->assertArrayHasKey('bullyIan', $result);
        $this->assertArrayHasKey('booLeeIan', $result);
        $this->assertFalse($result['debug']);
        $this->assertTrue($result['debugAgain']);
        $this->assertTrue($result['bullyIan']);
        $this->assertFalse($result['booLeeIan']);

        $this->assertArrayHasKey('memcached', $result);
        $this->assertArrayHasKey('weight', $result['memcached']);
        $this->assertFalse($result['memcached']['weight']);
        
        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('pgsql', $result['connections']);
        $this->assertArrayHasKey('password', $result['connections']['pgsql']);
        $this->assertTrue($result['connections']['pgsql']['password']);

        /*
         * Rewrite an integer
         */
        $contents = $writer->to($contents, ['aNumber' => 69]);
        $result = eval('?>'.$contents);

        $this->assertArrayHasKey('aNumber', $result);
        $this->assertEquals(69, $result['aNumber']);
    }

}