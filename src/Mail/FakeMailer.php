<?php namespace October\Rain\Mail;

use October\Rain\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Testing\Fakes\MailFake as MailFakeBase;

/**
 * FakeMailer
 *
 * @package october\mail
 * @author Alexey Bobkov, Samuel Georges, Alexander Guth
 */
class FakeMailer extends MailFakeBase
{
    /**
     * send a new message using a view
     */
    public function send($view, $data = [], $callback = null): void
    {
        if (!$view instanceof Mailable) {
            $view = $this->buildMailable($view, $data, $callback);
        }

        parent::send($view, $data = [], $callback = null);
    }

    /**
     * queue a new e-mail message for sending
     */
    public function queue($view, $data = null, $callback = null, $queue = null)
    {
        if (!$view instanceof Mailable) {
            $view = $this->buildMailable($view, $data, $callback, true);
        }

        return parent::queue($view, $data = null, $callback = null, $queue = null);
    }

    /**
     * mailablesOf a given type
     */
    protected function mailablesOf($type): Collection
    {
        return collect($this->mailables)->filter(function ($mailable) use ($type) {
            return $mailable->view === $type;
        });
    }

    /**
     * queuedMailablesOf of a given type
     */
    protected function queuedMailablesOf($type): Collection
    {
        return collect($this->queuedMailables)->filter(function ($mailable) use ($type) {
            return $mailable->view === $type;
        });
    }

    /**
     * buildMailable from a view file
     */
    public function buildMailable($view, $data, $callback, $queued = false)
    {
        $mailable = new Mailable;

        if ($queued) {
            $mailable->view($view)->withSerializedData($data);
        }
        else {
            $mailable->view($view, $data);
        }

        if ($callback !== null) {
            call_user_func($callback, $mailable);
        }

        return $mailable;
    }
}
