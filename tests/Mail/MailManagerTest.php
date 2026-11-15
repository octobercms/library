<?php

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use October\Rain\Mail\Mailer;
use October\Rain\Mail\MailManager;

/**
 * MailManagerTest
 */
class MailManagerTest extends TestCase
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
}
