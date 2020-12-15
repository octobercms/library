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

        $this->recipient = 'fake@localhost';
        $this->subject = 'MailFake test';
    }

    public function testSend()
    {
        $view = 'mail-test-view';

        Mail::send($view, [], function ($mailer) {
            $mailer->to($this->recipient);
            $mailer->subject($this->subject);
        });
        Mail::assertSent($view, 1);

        Mail::assertSent($view, function ($mailer) {
            return $mailer->hasTo($this->recipient);
        });

        Mail::assertSent($view, function ($mailer) {
            return $mailer->subject === $this->subject;
        });
    }

    public function testQueue()
    {
        $view = 'mail-test-queued-view';

        Mail::queue($view, [], function ($mailer) {
            $mailer->to($this->recipient);
            $mailer->subject($this->subject);
        });

        Mail::assertQueued($view, 1);

        Mail::assertQueued($view, function ($mailer) {
            return $mailer->hasTo($this->recipient);
        });

        Mail::assertQueued($view, function ($mailer) {
            return $mailer->subject === $this->subject;
        });
    }

    public function testIndexedArrayViews()
    {
        $views = ['html-view', 'plain-view'];
        $subject = 'test indexed array views';

        $this->arrayTests($views, $subject);
    }

    public function testNamedArrayViews()
    {
        $views = ['html' => 'html-view', 'text' => 'plain-view'];
        $subject = 'test named array views';

        $this->arrayTests($views, $subject);
    }

    public function testIndexedArrayViews_Queued()
    {
        $views = ['html-view', 'plain-view'];
        $subject = 'test indexed array views queued';

        $this->arrayTests($views, $subject, true);
    }

    public function testNamedArrayViews_Queued()
    {
        $views = ['html' => 'html-view', 'text' => 'plain-view'];
        $subject = 'test named array views queued';

        $this->arrayTests($views, $subject, true);
    }

    public function arrayTests($views, $subject, $queued = false)
    {
        $sendMethod = $queued ? 'queue' : 'send';
        $assertMethod = $queued ? 'assertQueued' : 'assertSent';

        Mail::{$sendMethod}($views, [], function ($mailer) use ($subject) {
            $mailer->to($this->recipient);
            $mailer->subject = $subject;
        });

        foreach (array_values($views) as $view) {
            Mail::{$assertMethod}($view, 1);

            Mail::{$assertMethod}($view, function ($mailer) {
                return $mailer->hasTo($this->recipient);
            });
            Mail::{$assertMethod}($view, function ($mailer) use ($subject) {
                return $mailer->subject === $subject;
            });
        }
    }
}
