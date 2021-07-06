<?php

use October\Rain\Mail\Mailer;
use October\Rain\Mail\FakeMailer;

/**
 * MailerTest
 */
class MailerTest extends TestCase
{
    /**
     * testSendWithFaker
     */
    public function testSendWithFaker()
    {
        $this->mockMailer();

        Mail::send('sendview', [], function ($mailer) {
            $mailer->subject('Message Subject');
            $mailer->to('single@address.tld');
        });

        Mail::assertSent('sendview', 1);

        Mail::assertSent('sendview', function ($mailer) {
            return $mailer->hasTo('single@address.tld');
        });

        Mail::assertSent('sendview', function ($mailer) {
            return $mailer->subject === 'Message Subject';
        });
    }

    /**
     * testQueueWithFaker
     */
    public function testQueueWithFaker()
    {
        Mail::queue('queueview', [], function ($mailer) {
            $mailer->subject('Message Subject');
            $mailer->to('single@address.tld');
        });

        Mail::queue('queueview', [], function ($mailer) {
            $mailer->subject('Second Message');
            $mailer->to('user@domain.tld');
        });

        Mail::assertQueued('queueview', 2);

        Mail::assertQueued('queueview', function ($mailer) {
            return $mailer->hasTo('single@address.tld');
            return $mailer->hasTo('user@domain.tld');
        });

        Mail::assertQueued('queueview', function ($mailer) {
            return $mailer->subject === 'Message Subject';
        });

        Mail::assertQueued('queueview', function ($mailer) {
            return $mailer->subject === 'Second Message';
        });
    }

    /**
     * testProcessRecipients
     */
    public function testProcessRecipients()
    {
        $mailer = $this->makeMailer();

        /*
         * String
         */
        $recipient = 'single@address.tld';
        $result = self::callProtectedMethod($mailer, 'processRecipients', [$recipient]);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('single@address.tld', $result);
        $this->assertNull($result['single@address.tld']);

        /*
         * Object
         */
        $recipients = (object) ['email' => 'user@domain.tld', 'name' => 'Adam Person'];
        $result = self::callProtectedMethod($mailer, 'processRecipients', [$recipients]);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('user@domain.tld', $result);
        $this->assertEquals('Adam Person', $result['user@domain.tld']);

        /*
         * Array
         */
        $recipients = [
            'admin@domain.tld' => 'Adam Person',
            'single@address.tld' => 'Pablo Francisco',
            'charles@barrington.tld' => 'Charlie Sheen'
        ];
        $result = self::callProtectedMethod($mailer, 'processRecipients', [$recipients]);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('admin@domain.tld', $result);
        $this->assertEquals('Adam Person', $result['admin@domain.tld']);
        $this->assertArrayHasKey('single@address.tld', $result);
        $this->assertEquals('Pablo Francisco', $result['single@address.tld']);
        $this->assertArrayHasKey('charles@barrington.tld', $result);
        $this->assertEquals('Charlie Sheen', $result['charles@barrington.tld']);

        /*
         * Array of Objects
         */
        $recipients = [
            (object) ['email' => 'person@one.tld', 'name' => 'First Person'],
            (object) ['email' => 'person@two.tld', 'name' => 'Second Person'],
            (object) ['email' => 'person@three.tld', 'address' => 'Some address somewhere', 'name' => 'Third Person']
        ];
        $result = self::callProtectedMethod($mailer, 'processRecipients', [$recipients]);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('person@one.tld', $result);
        $this->assertEquals('First Person', $result['person@one.tld']);
        $this->assertArrayHasKey('person@two.tld', $result);
        $this->assertEquals('Second Person', $result['person@two.tld']);
        $this->assertArrayHasKey('person@three.tld', $result);
        $this->assertEquals('Third Person', $result['person@three.tld']);

        /*
         * Array of Arrays
         */
        $recipients = [
            ['email' => 'person@one.tld', 'name' => 'First Person'],
            ['address' => 'person@two.tld', 'name' => 'Second Person'],
            ['email' => 'person@three.tld', 'address' => 'XXX@two.tld', 'name' => 'Third Person']
        ];
        $result = self::callProtectedMethod($mailer, 'processRecipients', [$recipients]);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('person@one.tld', $result);
        $this->assertEquals('First Person', $result['person@one.tld']);
        $this->assertArrayHasKey('person@two.tld', $result);
        $this->assertEquals('Second Person', $result['person@two.tld']);
        $this->assertArrayHasKey('person@three.tld', $result);
        $this->assertEquals('Third Person', $result['person@three.tld']);
    }

    //
    // Helpers
    //

    protected static function callProtectedMethod($object, $name, $params = [])
    {
        $className = get_class($object);
        $class = new ReflectionClass($className);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $params);
    }

    //
    // Mock
    //

    /**
     * mockMailer
     */
    protected function mockMailer()
    {
        Mail::swap(new FakeMailer);
    }

    /**
     * makeMailer
     */
    protected function makeMailer()
    {
        return new Mailer(new FactoryMailerTest, new SwiftMailerTest, new DispatcherMailerTest);
    }
}

class FactoryMailerTest extends \Illuminate\View\Factory
{
    public function __construct()
    {
    }
}

class DispatcherMailerTest extends \Illuminate\Events\Dispatcher
{
    public function __construct()
    {
    }
}

class SwiftMailerTest extends \Swift_Mailer
{
    public function __construct()
    {
    }
}
