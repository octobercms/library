<?php

use Illuminate\Filesystem\Filesystem;
use October\Rain\Translation\FileLoader;
use October\Rain\Translation\Translator;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 * @Iterations(5)
 */
class TranslatorBench
{
    /**
     * @var Translator translator
     */
    protected $translator;

    /**
     * init
     */
    public function init()
    {
        $loader = new FileLoader(new Filesystem, __DIR__.'/../../fixtures/lang');
        $this->translator = new Translator($loader, 'en');

        if ($this->translator->get('lang.test.hello_october') !== 'Hello October!') {
            throw new RuntimeException('Translation fixture mismatch');
        }
    }

    /**
     * @Subject
     */
    public function benchGet()
    {
        $this->translator->get('lang.test.hello_october');
    }

    /**
     * @Subject
     */
    public function benchGetWithReplacements()
    {
        $this->translator->get('lang.test.pagination', ['from' => 1, 'to' => 100, 'total' => 10]);
    }

    /**
     * @Subject
     */
    public function benchGetMissing()
    {
        $this->translator->get('lang.test.missing');
    }
}
