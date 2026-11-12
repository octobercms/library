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

    public function testMissingKeyHandlerIsCalled()
    {
        $missing = [];
        $this->translator->handleMissingKeysUsing(function ($key) use (&$missing) {
            $missing[] = $key;
        });

        $this->translator->get('lang.test.pagination', ['from' => 1, 'to' => 100, 'total' => 10]);
        $this->assertSame([], $missing);

        $this->assertEquals('lang.test.not_here', $this->translator->get('lang.test.not_here'));
        $this->assertSame(['lang.test.not_here'], $missing);
    }

    public function testDetermineLocalesUsingIsApplied()
    {
        $seen = null;
        $this->translator->determineLocalesUsing(function ($locales) use (&$seen) {
            $seen = $locales;

            return $locales;
        });

        $this->translator->get('lang.test.not_here');

        $this->assertContains('en', $seen);
    }
}
