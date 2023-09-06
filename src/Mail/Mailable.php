<?php namespace October\Rain\Mail;

use App;
use Site;
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
     * @var string siteContext is the active site for this mail message.
     */
    public $siteContext;

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
     * @param  array  $data
     * @return $this
     */
    public function withSerializedData($data)
    {
        $this->viewData['_current_locale'] = $this->locale ?: App::getLocale();

        $this->viewData['_current_site'] = Site::getSiteIdFromContext();

        foreach ($data as $param => $value) {
            $this->viewData[$param] = $this->getSerializedPropertyValue($value);
        }

        return $this;
    }

    /**
     * Set the subject for the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     * @return $this
     */
    protected function buildSubject($message)
    {
        if ($this->subject) {
            $message->subject($this->subject);
        }

        return $this;
    }

    /**
     * siteContext sets the site context of the message.
     *
     * @param  string  $siteId
     * @return $this
     */
    public function siteContext($siteId)
    {
        $this->siteContext = $siteId;

        return $this;
    }

    /**
     * withSiteContext runs the callback with the given site context.
     *
     * @param  string  $siteId
     * @param  \Closure  $callback
     * @return mixed
     */
    public function withSiteContext($siteId, $callback)
    {
        if (!$siteId) {
            return $callback();
        }

        return Site::withContext($siteId, $callback);
    }
}
