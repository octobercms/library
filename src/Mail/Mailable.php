<?php namespace October\Rain\Mail;

use App;
use Illuminate\Mail\Mailable as MailableBase;

/**
 * Generic mailable class.
 *
 * @package october\mail
 * @author Alexey Bobkov, Samuel Georges
 */
class Mailable extends MailableBase
{
    use \Illuminate\Bus\Queueable;
    use \Illuminate\Queue\SerializesModels;

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this;
    }

    /**
     * Build the view data for the message.
     *
     * @return array
     */
    public function buildViewData()
    {
        $data = $this->viewData;

        foreach ($data as $param => $value) {
            $data[$param] = $this->getRestoredPropertyValue($value);
        }

        return $data;
    }

    /**
     * Set serialized view data for the message.
     *
     * @param array $data
     * @return $this
     */
    public function withSerializedData($data)
    {
        $this->viewData['_current_locale'] = $this->locale ?: App::getLocale();

        foreach ($data as $param => $value) {
            $this->viewData[$param] = $this->getSerializedPropertyValue($value);
        }

        return $this;
    }

    /**
     * Set the subject for the message.
     *
     * @param \Illuminate\Mail\Message $message
     * @return $this
     */
    protected function buildSubject($message)
    {
        if ($this->subject) {
            $message->subject($this->subject);
        }

        return $this;
    }
}
