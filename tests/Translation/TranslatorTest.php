<?php

use Illuminate\Filesystem\Filesystem;
use October\Rain\Events\Dispatcher;
use October\Rain\Translation\FileLoader;
use October\Rain\Translation\Translator;

class TranslatorTest extends TestCase
{
    /**
     * @var Translator
     */
    private $translator;

    public function setUp(): void
    {
        parent::setUp();

        $path       = __DIR__ . '/../fixtures/lang';
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

    public function testChoice()
    {
        $this->assertEquals(
            'Page',
            $this->translator->choice('lang.test.choice', 1)
        );
        $this->assertEquals(
            'Pages',
            $this->translator->choice('lang.test.choice', 2)
        );
    }

    /**
     * Test case for https://github.com/octobercms/october/issues/4858
     *
     * @return void
     */
    public function testChoiceSublocale()
    {
        $this->translator->setLocale('en-au');

        $this->assertEquals(
            'Page',
            $this->translator->choice('lang.test.choice', 1)
        );
        $this->assertEquals(
            'Pages',
            $this->translator->choice('lang.test.choice', 2)
        );
    }

    public function testOverrideWithBeforeResolveEvent()
    {
        $eventsDispatcher = $this->createMock(Dispatcher::class);
        $eventsDispatcher
            ->expects($this->exactly(2))
            ->method('fire')
            ->will($this->onConsecutiveCalls('Hello Override!', null));
        $this->translator->setEventDispatcher($eventsDispatcher);

        $this->assertEquals('Hello Override!', $this->translator->get('lang.test.hello_override'));
        $this->assertEquals('Hello October!', $this->translator->get('lang.test.hello_october'));
    }
}
