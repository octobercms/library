<?php namespace October\Rain\Html;

use Illuminate\Html\FormBuilder as FormBuilderBase;
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
     * The reserved form open attributes.
     * @var array
     */
    protected $reserved = ['method', 'url', 'route', 'action', 'files', 'request', 'model'];

    /**
     * The reserved form open attributes.
     * @var array
     */
    protected $reservedAjax = ['request', 'success', 'error', 'confirm', 'redirect', 'update', 'data'];

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
        $method = strtoupper(array_get($options, 'method', 'post'));
        $request = array_get($options, 'request');
        $model = array_get($options, 'model');

        if ($model)
            $this->model = $model;

        $append = $this->requestHandler($request);

        if ($method != 'GET')
            $append .= $this->sessionKey();

        return parent::open($options) . $append;
    }

    /**
     * Helper for opening a form used for an AJAX call.
     * @param string $handler Request handler name, eg: onUpdate
     * @param array $options
     * @return string
     */
    public function ajax($handler, array $options = [])
    {
        if (is_array($handler))
            $handler = implode('::', $handler);

        $attributes = array_merge(

            ['data-request' => $handler],
            array_except($options, $this->reservedAjax)

        );

        $ajaxAttributes = array_diff_key($options, $attributes);
        foreach ($ajaxAttributes as $property => $value) {
            $attributes['data-request-' . $property] = $value;
        }

        return $this->open($attributes);
    }

    /**
     * Helper for getting form values.
     */
    public function value($name, $value = null)
    {
        return $this->getValueAttribute($name, $value);
    }

    /**
     * Returns a hidden HTML input, supplying the session key value.
     * @return string
     */
    protected function requestHandler($name = null)
    {
        if (!strlen($name))
            return '';

        return $this->hidden('_handler', $name);
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
