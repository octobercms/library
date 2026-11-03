<?php

use October\Rain\Mail\FakeMailer;
use October\Rain\Support\Facades\Mail;
use Illuminate\Mail\MailManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;

/**
 * FakeMailerTest
 */
class FakeMailerTest extends TestCase
{
    /**
     * @var Container container before the test
     */
    protected $container;

    /**
     * @var mixed facadeApplication before the test
     */
    protected $facadeApplication;

    /**
     * setUp a container with a mail manager, as the facade resolves it
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container = Container::getInstance();
        $this->facadeApplication = Facade::getFacadeApplication();

        $app = new Container;
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstance('mail.manager');

        $app->instance('config', new Repository([
            'mail' => [
                'default' => 'array',
                'mailers' => ['array' => ['transport' => 'array']]
            ]
        ]));

        $app->singleton('mail.manager', function ($app) {
            return new MailManager($app);
        });
    }

    /**
     * tearDown
     */
    public function tearDown(): void
    {
        Facade::clearResolvedInstance('mail.manager');
        Container::setInstance($this->container);
        Facade::setFacadeApplication($this->facadeApplication);

        parent::tearDown();
    }

    /**
     * testFakeSwapsTheFacade
     */
    public function testFakeSwapsTheFacade()
    {
        $fake = Mail::fake();

        $this->assertInstanceOf(FakeMailer::class, $fake);
        $this->assertSame($fake, Mail::getFacadeRoot());
        $this->assertInstanceOf(MailManager::class, $fake->manager);
    }

    /**
     * testFakeTwiceKeepsTheRealManager
     */
    public function testFakeTwiceKeepsTheRealManager()
    {
        $first = Mail::fake();
        $second = Mail::fake();

        $this->assertNotSame($first, $second);
        $this->assertSame($first->manager, $second->manager);
        $this->assertNotInstanceOf(FakeMailer::class, $second->manager);
    }
}
