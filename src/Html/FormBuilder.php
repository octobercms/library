<?php namespace October\Rain\Html;

use Illuminate\Html\FormBuilder as FormBuilderBase;
use Illuminate\Html\HtmlBuilder;
use Illuminate\Routing\UrlGenerator;

/**
 * Form builder
 *
 * Extension of illuminate/html, injects a session key to each form opening.
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class FormBuilder extends FormBuilderBase
{

    /**
     * The session key used by the form builder.
     * @var string
     */
    protected $sessionKey;

    /**
     * Create a new form builder instance.
     *
     * @param \Illuminate\Html\HtmlBuilder  $html
     * @param \Illuminate\Routing\UrlGenerator  $url
     * @param string  $csrfToken
     * @param string  $sessionKey
     * @return void
     */
    public function __construct(HtmlBuilder $html, UrlGenerator $url, $csrfToken, $sessionKey)
    {
        $this->sessionKey = $sessionKey;
        parent::__construct($html, $url, $csrfToken);
    }

    /**
     * Open up a new HTML form and includes a session key.
     * @param array $options
     * @return string
     */
    public function open(array $options = [])
    {
        return parent::open($options) . $this->sessionKey();
    }

    /**
     * Helper for opening a form used for an AJAX call.
     * @param string $handler Request handler name, eg: onUpdate
     * @param array $options
     * @return string
     */
    public function ajax($handler, array $options = [])
    {
        $options['data-request'] = $handler;
        return $this->open($options);
    }

    /**
     * Returns a hidden HTML input, supplying the session key value.
     * @return string
     */
    protected function sessionKey()
    {
        return $this->hidden('_session_key', post('_session_key', $this->sessionKey));
    }

    /**
     * Returns the active session key, used fr deferred bindings.
     * @return string
     */
    public function getSessionKey()
    {
        return $this->sessionKey;
    }

}
