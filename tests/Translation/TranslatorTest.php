<?php

use Illuminate\Filesystem\Filesystem;
use October\Rain\Translation\FileLoader;
use October\Rain\Translation\Translator;

class TranslatorTest extends TestCase
{
    /**
     * @var Translator
     */
    protected $translator;

    public function setUp(): void
    {
        parent::setUp();

        $path = __DIR__ . '/../fixtures/lang';
        $fileLoader = new FileLoader(new Filesystem(), $path);
        $translator = new Translator($fileLoader, 'en');
        $this->translator = $translator;
    }

    public function testSimilarWordsParsing()
    {
        $this->assertEquals(
            'Displayed records: 1-100 of 10',
            $this->translator->get('lang.test.pagination', ['from' => 1, 'to' => 100, 'total' => 10])
        );
    }

    public function testOverrideWithSet()
    {
        $this->assertEquals('lang.test.hello_override', $this->translator->get('lang.test.hello_override'));
        $this->translator->set('lang.test.hello_override', 'Hello Override!');
        $this->assertEquals('Hello Override!', $this->translator->get('lang.test.hello_override'));
    }
}
