<?php namespace October\Rain\Router;

use App;
use Illuminate\Routing\Redirector as RedirectorBase;

/**
 * CoreRedirector adds extra events to the base redirector and ensures the "intended"
 * session is different for the frontend and backend contexts.
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class CoreRedirector extends RedirectorBase
{
    /**
     * intended creates a new redirect response to the previously intended location.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function intended($default = '/', $status = 302, $headers = [], $secure = null)
    {
        if (!App::runningInFrontend()) {
            return parent::intended($default, $status, $headers, $secure);
        }

        $path = $this->session->pull('url.cms.intended', $default);

        return $this->to($path, $status, $headers, $secure);
    }

    /**
     * getIntendedUrl from the session.
     */
    public function getIntendedUrl()
    {
        if (!App::runningInFrontend()) {
            return parent::getIntendedUrl();
        }

        return $this->session->get('url.cms.intended');
    }

    /**
     * setIntendedUrl in the session.
     */
    public function setIntendedUrl($url)
    {
        if (!App::runningInFrontend()) {
            return parent::setIntendedUrl($url);
        }

        $this->session->put('url.cms.intended', $url);
        return $this;
    }
}
