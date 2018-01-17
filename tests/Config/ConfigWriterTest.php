<?php

use October\Rain\Config\ConfigWriter;

class ConfigWriterTest extends TestCase
{
    public function testToFile()
    {
        $writer = new ConfigWriter;

        $filePath = __DIR__ . '/../fixtures/config/sample-config.php';
        $tmpFile = __DIR__ . '/../fixtures/config/temp-config.php';
        copy($filePath, $tmpFile);

        $contents = $writer->toFile($tmpFile, ['connections.sqlite.driver' => 'sqlbite']);

        $contents = $writer->toFile($tmpFile, ['envkey' => 'env_key_value']);
        $contents = $writer->toFile($tmpFile, ['envlevels.envlevel1.envlevel2' => 'env_level_2_value']);

        $contents = $writer->toFile($tmpFile, ['envdefaultkeystring' => 'env_default_value_string_new']);
        $contents = $writer->toFile($tmpFile, ['envdefaultlevels.envdefaultlevel1.envdefaultlevel2' => 'env_default_level_2_value_new']);

        $result = include $tmpFile;
        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('sqlite', $result['connections']);
        $this->assertArrayHasKey('driver', $result['connections']['sqlite']);
        $this->assertEquals('sqlbite', $result['connections']['sqlite']['driver']);

        unlink($tmpFile);
    }

    public function testToContent()
    {
        $writer = new ConfigWriter;

        /*
         * Rewrite a single level string
         */
        $contents = file_get_contents(__DIR__ . '/../fixtures/config/sample-config.php');
        $contents = $writer->toContent($contents, ['url' => 'http://octobercms.com']);
        $result = eval('?>' . $contents);

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('http://octobercms.com', $result['url']);

        /*
         * Rewrite a second level string
         */
        $contents = $writer->toContent($contents, ['memcached.host' => '69.69.69.69']);
        $result = eval('?>' . $contents);

        $this->assertArrayHasKey('memcached', $result);
        $this->assertArrayHasKey('host', $result['memcached']);
        $this->assertEquals('69.69.69.69', $result['memcached']['host']);

        /*
         * Rewrite a third level string
         */
        $contents = $writer->toContent($contents, ['connections.mysql.host' => '127.0.0.1']);
        $result = eval('?>' . $contents);

        $this->assertArrayHasKey('connections', $result);
        $this->assertArrayHasKey('mysql', $result['connections']);
        $this->assertArrayHasKey('host', $result['connections']['mysql']);
        $this->assertEquals('127.0.0.1', $result['connections']['mysql']['host']);

        /*
         * Test alternative quoting
         */
        $contents = $writer->toContent($contents, ['timezone' => 'The Fifth Dimension']);
        $contents = $writer->toContent($contents, ['timezoneAgain' => 'The "Sixth" Dimension']);
        $result = eval('?>' . $contents);

        $this->assertArrayHasKey('timezone', $result);
        $this->assertArrayHasKey('timezoneAgain', $result);
        $this->assertEquals('The Fifth Dimension', $result['timezone']);
        $this->assertEquals('The "Sixth" Dimension', $result['timezoneAgain']);

        /*
         * Rewrite a boolean
         */
        $contents = $writer->toContent($contents, ['debug' => false]);
        $contents = $writer->toContent($contents, ['debugAgain' => true]);
        $contents = $writer->toContent($contents, ['bullyIan' => true]);
        $contents = $writer->toContent($contents, ['booLeeIan' => false]);
        $contents = $writer->toContent($contents, ['memcached.weight' => false]);
        $contents = $writer->toContent($contents, ['connections.pgsql.password' => true]);
        $result = eval('?>' . $contents);

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
        $this->assertEquals('', $result['connections']['sqlsrv']['password']);

        /*
         * Rewrite an integer
         */
        $contents = $writer->toContent($contents, ['aNumber' => 69]);
        $result = eval('?>' . $contents);

        $this->assertArrayHasKey('aNumber', $result);
        $this->assertEquals(69, $result['aNumber']);

        /*
         * Only first match should be replaced
         */
        $contents = $writer->toContent($contents, ['default' => 'pgsql']);
        $result = eval('?>' . $contents);

        $this->assertArrayHasKey('default', $result);
        $this->assertEquals('pgsql', $result['default']);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('redis', $result);
        $this->assertInternalType('array', $result['redis']);
        $this->assertArrayHasKey('default', $result['redis']);
        $this->assertInternalType('array', $result['redis']['default']);
        $this->assertArrayHasKey('host', $result['redis']['default']);
        $this->assertArrayHasKey('password', $result['redis']['default']);
        $this->assertArrayHasKey('port', $result['redis']['default']);
        $this->assertArrayHasKey('database', $result['redis']['default']);
        $this->assertEquals('127.0.0.1', $result['redis']['default']['host']);
        $this->assertEquals(null, $result['redis']['default']['password']);


        /*
         * Test env like 'key'=>env('KEY'')
         */
        $contents = $writer->toContent($contents, ['envkey' => 'env_value_new']);
        $contents = $writer->toContent($contents, ['envkeytrue' => true]);
        $contents = $writer->toContent($contents, ['envkeyfalse' => false]);
        $contents = $writer->toContent($contents, ['envkeynull' => null]);
        $contents = $writer->toContent($contents, ['envlevels.envlevel1.envlevel2' => 'env_level_2_value']);
        $result = eval('?>' . $contents);

        $this->assertArrayHasKey('envkey', $result);
        $this->assertEquals('env_value_new', $result['envkey']);

        $this->assertArrayHasKey('envkeytrue', $result);
        $this->assertTrue($result['envkeytrue']);

        $this->assertArrayHasKey('envkeyfalse', $result);
        $this->assertFalse($result['envkeyfalse']);

        $this->assertArrayHasKey('envkeynull', $result);
        $this->assertNull($result['envkeynull']);

        $this->assertArrayHasKey('envlevel2', $result['envlevels']['envlevel1']);
        $this->assertEquals('env_level_2_value', $result['envlevels']['envlevel1']['envlevel2']);

        /*
         *  Test env like 'key'=>env('KEY','value')
         */
        $contents = $writer->toContent($contents, ['envdefaultkeystring' => 'env_default_value_string_new']);
        $contents = $writer->toContent($contents, ['envdefaultkeytrue' => true]);
        $contents = $writer->toContent($contents, ['envdefaultkeyfalse' => false]);
        $contents = $writer->toContent($contents, ['envdefaultkeynull' => null]);
        $contents = $writer->toContent($contents, ['envdefaultlevels.envdefaultlevel1.envdefaultlevel2' => 'env_default_level_2_value_new']);
        $result = eval('?>' . $contents);

        $this->assertArrayHasKey('envdefaultkeystring', $result);
        $this->assertEquals('env_default_value_string_new', $result['envdefaultkeystring']);

        $this->assertArrayHasKey('envdefaultkeytrue', $result);
        $this->assertTrue($result['envdefaultkeytrue']);

        $this->assertArrayHasKey('envdefaultkeyfalse', $result);
        $this->assertFalse($result['envdefaultkeyfalse']);

        $this->assertArrayHasKey('envdefaultkeynull', $result);
        $this->assertNull($result['envdefaultkeynull']);

        $this->assertArrayHasKey('envdefaultlevel2', $result['envdefaultlevels']['envdefaultlevel1']);
        $this->assertEquals('env_default_level_2_value_new', $result['envdefaultlevels']['envdefaultlevel1']['envdefaultlevel2']);

    }
}
