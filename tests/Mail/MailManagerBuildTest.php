<?php

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use October\Rain\Mail\Mailable;
use October\Rain\Mail\Mailer;
use October\Rain\Mail\MailManager;

/**
 * MailManagerBuildTest
 */
class MailManagerBuildTest extends TestCase
{
    /**
     * testBuildReturnsTheRainMailer confirms on-demand mailers use the Rain mailer class
     */
    public function testBuildReturnsTheRainMailer()
    {
        $app = new Container;
        $app->instance('view', $this->createMock(Illuminate\Contracts\View\Factory::class));
        $app->instance('events', new Dispatcher($app));

        $manager = new MailManager($app);

        $this->assertInstanceOf(Mailer::class, $manager->build(['transport' => 'array']));
    }

    /**
     * testViewDataCallbackIsApplied confirms buildViewDataUsing contributes view data
     */
    public function testViewDataCallbackIsApplied()
    {
        Mailable::buildViewDataUsing(fn () => ['extra' => 'value']);

        try {
            $data = (new Mailable)->with('name', 'Jeff')->buildViewData();
        }
        finally {
            Mailable::buildViewDataUsing(fn () => []);
        }

        $this->assertSame('value', $data['extra'] ?? null);
        $this->assertSame('Jeff', $data['name'] ?? null);
    }
}
