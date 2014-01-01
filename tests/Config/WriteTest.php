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
        $contents = $writer->to($contents, 'url', 'http://octobercms.com');

        echo $contents;
        $result = eval('?>'.$contents);

        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('http://octobercms.com', $result['url']);

        /*
         * Rewrite a boolean
         */
    }

}