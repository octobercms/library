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

    public function setUp()
    {
        parent::setUp();

        $path       = __DIR__ . '/../fixtures/lang';
        $fileLoader = new FileLoader(new Filesystem(), $path);
        $translator = new Translator($fileLoader, 'en');
        $this->translator = $translator;
    }

    public function testSimilarWordsParsing()
    {
        $this->assertEquals('Displayed records: 1-100 of 10',
            $this->translator->get('lang.test.pagination', ['from' => 1, 'to' => 100, 'total' => 10])
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
