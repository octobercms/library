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
     * @var string forceMailer forces the mailer to use.
     */
    public $forceMailer;

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

        $this->viewData['_current_site'] = $this->siteContext ?: Site::getSiteIdFromContext();

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
     * withLocale acts as a hook to also apply the site context
     *
     * @param  string  $locale
     * @param  \Closure  $callback
     * @return mixed
     */
    public function withLocale($locale, $callback)
    {
        if (!$this->siteContext) {
            return parent::withLocale($locale, $callback);
        }

        return Site::withContext($this->siteContext, function() use ($locale, $callback) {
            return parent::withLocale($locale, $callback);
        });
    }

    /**
     * forceMailer forces sending using a different mail driver, useful if lazy loading
     * the mail driver configuration for multisite.
     * @param  string  $mailer
     * @return $this
     */
    public function forceMailer($mailer)
    {
        $this->forceMailer = $mailer;

        return $this;
    }

    /**
     * mailer sets the name of the mailer that should send the message.
     * @param  string  $mailer
     * @return $this
     */
    public function mailer($mailer)
    {
        $this->mailer = $this->forceMailer ?: $mailer;

        return $this;
    }
}
