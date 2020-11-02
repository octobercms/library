<?php

class MailFakeTest extends TestCase
{
    public function setUp(): void
    {
        $this->mailer = new \October\Rain\Support\Testing\Fakes\MailFake();

        // Mock App facade
        if (!class_exists('App')) {
            class_alias('\Illuminate\Support\Facades\App', 'App');
        }

        App::shouldReceive('getLocale')->andreturn('en/US');

        $this->view = 'mail-test-view';
        $this->recipient = 'fake@localhost';
        $this->subject = 'MailFake test';
    }

    public function testSend()
    {
        $this->mailer->send($this->view, [], function ($mailer) {
            $mailer->to($this->recipient);
            $mailer->subject('MailFake test');
        });

        $this->mailer->assertSent($this->view, 1);

        $this->mailer->assertSent($this->view, function ($mailer) {
            return $mailer->hasTo($this->recipient);
        });

        $this->mailer->assertSent($this->view, function ($mailer) {
            return $mailer->subject === $this->subject;
        });
    }

    public function testQueue()
    {
        $this->mailer->queue($this->view, [], function ($mailer) {
            $mailer->to($this->recipient);
            $mailer->subject($this->subject);
        });

        $this->mailer->assertQueued($this->view, 1);

        $this->mailer->assertQueued($this->view, function ($mailer) {
            return $mailer->hasTo($this->recipient);
        });

        $this->mailer->assertQueued($this->view, function ($mailer) {
            return $mailer->subject === $this->subject;
        });
    }
}
