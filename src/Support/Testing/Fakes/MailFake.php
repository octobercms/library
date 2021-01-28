<?php namespace October\Rain\Support\Testing\Fakes;

use October\Rain\Mail\Mailable;

class MailFake extends \Illuminate\Support\Testing\Fakes\MailFake
{
    /**
     * Get all of the mailed mailables for a given type.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function mailablesOf($type)
    {
        return collect($this->mailables)->filter(function ($mailable) use ($type) {
            return $mailable->view === $type;
        });
    }

    /**
     * Get all of the queued mailables for a given type.
     *
     * @param  string  $type
     * @return \Illuminate\Support\Collection
     */
    protected function queuedMailablesOf($type)
    {
        return collect($this->queuedMailables)->filter(function ($mailable) use ($type) {
            return $mailable->view === $type;
        });
    }

    /**
     * Send a new message using a view.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @return void
     */
    public function send($view, $data = [], $callback = null)
    {
        if (!$view instanceof Mailable) {
            $view = $this->buildMailable($view, $data, $callback);
        }

        parent::send($view, $data = [], $callback = null);
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($view, $data = null, $callback = null, $queue = null)
    {
        if (!$view instanceof Mailable) {
            $view = $this->buildMailable($view, $data, $callback, true);
        }

        return parent::queue($view, $data = null, $callback = null, $queue = null);
    }

    /**
     * Create a Mailable object from a view file.
     *
     * @param  string|array  $view
     * @param  array  $data
     * @param  \Closure|string  $callback
     * @param  bool  $queued
     * @return \October\Rain\Mail\Mailable
     */
    public function buildMailable($view, $data, $callback, $queued = false)
    {
        $mailable = new Mailable;

        if ($queued) {
            $mailable->view($view)->withSerializedData($data);
        } else {
            $mailable->view($view, $data);
        }

        if ($callback !== null) {
            call_user_func($callback, $mailable);
        }
        return $mailable;
    }
}
