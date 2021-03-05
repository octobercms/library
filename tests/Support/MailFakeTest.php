<?php

use October\Rain\Support\Testing\Fakes\MailFake;

class MailFakeTest extends TestCase
{
    public function setUp(): void
    {
        // Mock App facade
        if (!class_exists('App')) {
            class_alias('\Illuminate\Support\Facades\App', 'App');
        }
        App::shouldReceive('getLocale')->andreturn('en/US');

        // Mock Mail facade
        if (!class_exists('Mail')) {
            class_alias('\October\Rain\Support\Facades\Mail', 'Mail');
        }
        Mail::swap(new MailFake());

        $this->view = 'mail-test-view';
        $this->recipient = 'fake@localhost';
        $this->subject = 'MailFake test';
    }

    public function testSend()
    {
        Mail::send($this->view, [], function ($mailer) {
            $mailer->to($this->recipient);
            $mailer->subject($this->subject);
        });
        Mail::assertSent($this->view, 1);

        Mail::assertSent($this->view, function ($mailer) {
            return $mailer->hasTo($this->recipient);
        });

        Mail::assertSent($this->view, function ($mailer) {
            return $mailer->subject === $this->subject;
        });
    }

    public function testQueue()
    {
        Mail::queue($this->view, [], function ($mailer) {
            $mailer->to($this->recipient);
            $mailer->subject($this->subject);
        });

        Mail::assertQueued($this->view, 1);

        Mail::assertQueued($this->view, function ($mailer) {
            return $mailer->hasTo($this->recipient);
        });

        Mail::assertQueued($this->view, function ($mailer) {
            return $mailer->subject === $this->subject;
        });
    }
}
