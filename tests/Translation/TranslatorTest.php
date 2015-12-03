<?php

use Illuminate\Filesystem\Filesystem;
use October\Rain\Translation\FileLoader;
use October\Rain\Translation\Translator;

class TranslatorTest extends TestCase
{
    public function testSimilarWordsParsing()
    {
        $path       = __DIR__ . '/../fixtures/lang';
        $fileLoader = new FileLoader(new Filesystem(), $path);
        $translator = new Translator($fileLoader, 'en');

        $this->assertEquals('Displayed records: 1-100 of 10',
            $translator->get('lang.test.pagination', ['from' => 1, 'to' => 100, 'total' => 10])
        );
    }
}
