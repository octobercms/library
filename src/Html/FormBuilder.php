<?php namespace October\Rain\Html;

use Illuminate\Session\Store as Session;
use Illuminate\Routing\UrlGenerator as UrlGeneratorBase;

/**
 * FormBuilder
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class FormBuilder
{
    use \Illuminate\Support\Traits\Macroable;

    /**
     * @var \October\Rain\Html\HtmlBuilder html builder instance
     */
    protected $html;

    /**
     * @var \Illuminate\Routing\UrlGenerator url generator instance.
     */
    protected $url;

    /**
     * @var string csrfToken used by the form builder.
     */
    protected $csrfToken;

    /**
     * @var \Illuminate\Session\Store session store implementation.
     */
    protected $session;

    /**
     * @var mixed model instance for the form.
     */
    protected $model;

    /**
     * @var array labels is an array of label names we've created.
     */
    protected $labels = [];

    /**
     * @var array reserved form open attributes.
     */
    protected $reserved = [
        'method',
        'url',
        'route',
        'action',
        'files',
        'request',
        'model',
        'sessionKey'
    ];

    /**
     * @var array reservedAjax form open attributes.
     */
    protected $reservedAjax = [
        'request',
        'success',
        'error',
        'complete',
        'confirm',
        'redirect',
        'update',
        'data',
        'validate',
        'flash',
        'bulk',
        'download'
     ];

    /**
     * @var array spoofedMethods are form methods that should be spoofed, in uppercase.
     */
    protected $spoofedMethods = ['DELETE', 'PATCH', 'PUT'];

    /**
     * @var array skipValueTypes of inputs to not fill values on by default.
     */
    protected $skipValueTypes = ['file', 'password', 'checkbox', 'radio'];

    /**
     * @var string sessionKey used by the form builder.
     */
    protected $sessionKey;

    /**
     * __construct a new form builder instance.
     *
     * @param \October\Rain\Html\HtmlBuilder  $html
     * @param \Illuminate\Routing\UrlGenerator  $url
     * @param string  $csrfToken
     * @param string  $sessionKey
     * @return void
     */
    public function __construct(HtmlBuilder $html, UrlGeneratorBase $url, $csrfToken, $sessionKey)
    {
        $this->url = $url;
        $this->html = $html;
        $this->csrfToken = $csrfToken;
        $this->sessionKey = $sessionKey;
    }

    /**
     * open up a new HTML form and includes a session key.
     * @param array $options
     * @return string
     */
    public function open(array $options = [])
    {
        $method = strtoupper(array_get($options, 'method', 'post'));
        $request = array_get($options, 'request');
        $model = array_get($options, 'model');

        if ($model) {
            $this->model = $model;
        }

        $append = $this->requestHandler($request);

        if ($method !== 'GET') {
            $append .= $this->sessionKey(array_get($options, 'sessionKey'));
        }

        $attributes = [];

        // We need to extract the proper method from the attributes. If the method is
        // something other than GET or POST we'll use POST since we will spoof the
        // actual method since forms don't support the reserved methods in HTML.
        $attributes['method'] = $this->getMethod($method);

        $attributes['action'] = $this->getAction($options);

        $attributes['accept-charset'] = 'UTF-8';

        // If the method is PUT, PATCH or DELETE we will need to add a spoofer hidden
        // field that will instruct the Symfony request to pretend the method is a
        // different method than it actually is, for convenience from the forms.
        $append .= $this->getAppendage($method);

        if (isset($options['files']) && $options['files']) {
            $options['enctype'] = 'multipart/form-data';
        }

        // Finally we're ready to create the final form HTML field. We will attribute
        // format the array of attributes. We will also add on the appendage which
        // is used to spoof requests for this PUT, PATCH, etc. methods on forms.
        $attributes = array_merge(
            $attributes,
            array_except($options, $this->reserved)
        );

        // Finally, we will concatenate all of the attributes into a single string so
        // we can build out the final form open statement. We'll also append on an
        // extra value for the hidden _method field if it's needed for the form.
        $attributes = $this->html->attributes($attributes);

        return '<form'.$attributes.'>'.$append;
    }

    /**
     * ajax helper for opening a form used for an AJAX call.
     * @param string $handler Request handler name, eg: onUpdate
     * @param array $options
     * @return string
     */
    public function ajax($handler, array $options = [])
    {
        if (is_array($handler)) {
            $handler = implode('::', $handler);
        }

        $attributes = array_merge([
            'data-request' => $handler
        ], array_except($options, $this->reservedAjax));

        $ajaxAttributes = array_diff_key($options, $attributes);
        foreach ($ajaxAttributes as $property => $value) {
            $attributes['data-request-' . $property] = $value;
        }

        // The `files` option is a hybrid
        if (isset($options['files'])) {
            $attributes['data-request-files'] = $options['files'];
        }

        return $this->open($attributes);
    }

    /**
     * model creates a new model based form builder.
     * @param  mixed  $model
     * @param  array  $options
     * @return string
     */
    public function model($model, array $options = [])
    {
        $this->model = $model;

        return $this->open($options);
    }

    /**
     * setModel instance on the form builder.
     * @param  mixed  $model
     * @return void
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * close the current form.
     * @return string
     */
    public function close()
    {
        $this->labels = [];

        $this->model = null;

        return '</form>';
    }

    /**
     * token generates a hidden field with the current CSRF token.
     * @return string
     */
    public function token()
    {
        $token = !empty($this->csrfToken)
            ? $this->csrfToken
            : $this->session->token();

        return $this->hidden('_token', $token);
    }

    /**
     * label creates a form label element.
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function label($name, $value = null, $options = [])
    {
        $this->labels[] = $name;

        $options = $this->html->attributes($options);

        $value = e($this->formatLabel($name, $value));

        return '<label for="'.$name.'"'.$options.'>'.$value.'</label>';
    }

    /**
     * formatLabel value.
     * @param  string  $name
     * @param  string|null  $value
     * @return string
     */
    protected function formatLabel($name, $value)
    {
        return $value ?: ucwords(str_replace('_', ' ', $name));
    }

    /**
     * input creates a form input field.
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function input($type, $name, $value = null, $options = [])
    {
        if (!isset($options['name'])) {
            $options['name'] = $name;
        }

        // We will get the appropriate value for the given field. We will look for the
        // value in the session for the value in the old input data then we'll look
        // in the model instance if one is set. Otherwise we will just use empty.
        $id = $this->getIdAttribute($name, $options);

        if (!in_array($type, $this->skipValueTypes)) {
            $value = $this->getValueAttribute($name, $value);
        }

        // Once we have the type, value, and ID we can merge them into the rest of the
        // attributes array so we can convert them into their HTML attribute format
        // when creating the HTML element. Then, we will return the entire input.
        $merge = compact('type', 'value', 'id');

        $options = array_merge($options, $merge);

        return '<input'.$this->html->attributes($options).'>';
    }

    /**
     * text input field.
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function text($name, $value = null, $options = [])
    {
        return $this->input('text', $name, $value, $options);
    }

    /**
     * password input field.
     * @param  string  $name
     * @param  array   $options
     * @return string
     */
    public function password($name, $options = [])
    {
        return $this->input('password', $name, '', $options);
    }

    /**
     * hidden input field.
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function hidden($name, $value = null, $options = [])
    {
        return $this->input('hidden', $name, $value, $options);
    }

    /**
     * email input field.
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function email($name, $value = null, $options = [])
    {
        return $this->input('email', $name, $value, $options);
    }

    /**
     * number input field.
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function number($name, $value = null, $options = [])
    {
        return $this->input('number', $name, $value, $options);
    }

    /**
     * url input field.
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function url($name, $value = null, $options = [])
    {
        return $this->input('url', $name, $value, $options);
    }

    /**
     * file input field.
     * @param  string  $name
     * @param  array   $options
     * @return string
     */
    public function file($name, $options = [])
    {
        return $this->input('file', $name, null, $options);
    }

    //
    // Textarea
    //

    /**
     * textarea input field.
     * @param  string  $name
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function textarea($name, $value = null, $options = [])
    {
        if (!isset($options['name'])) {
            $options['name'] = $name;
        }

        // Next we will look for the rows and cols attributes, as each of these are put
        // on the textarea element definition. If they are not present, we will just
        // assume some sane default values for these attributes for the developer.
        $options = $this->setTextAreaSize($options);

        $options['id'] = $this->getIdAttribute($name, $options);

        $value = (string) $this->getValueAttribute($name, $value);

        unset($options['size']);

        // Next we will convert the attributes into a string form. Also we have removed
        // the size attribute, as it was merely a short-cut for the rows and cols on
        // the element. Then we'll create the final textarea elements HTML for us.
        $options = $this->html->attributes($options);

        return '<textarea'.$options.'>'.e($value).'</textarea>';
    }

    /**
     * setTextAreaSize on the attributes.
     * @param  array  $options
     * @return array
     */
    protected function setTextAreaSize($options)
    {
        if (isset($options['size'])) {
            return $this->setQuickTextAreaSize($options);
        }

        // If the "size" attribute was not specified, we will just look for the regular
        // columns and rows attributes, using sane defaults if these do not exist on
        // the attributes array. We'll then return this entire options array back.
        $cols = array_get($options, 'cols', 50);

        $rows = array_get($options, 'rows', 10);

        return array_merge($options, compact('cols', 'rows'));
    }

    /**
     * setQuickTextAreaSize using the quick "size" attribute.
     *
     * @param  array  $options
     * @return array
     */
    protected function setQuickTextAreaSize($options)
    {
        $segments = explode('x', $options['size']);

        return array_merge($options, ['cols' => $segments[0], 'rows' => $segments[1]]);
    }

    //
    // Select
    //

    /**
     * select box field with empty option support.
     * @param  string  $name
     * @param  array   $list
     * @param  string  $selected
     * @param  array   $options
     * @return string
     */
    public function select($name, $list = [], $selected = null, $options = [])
    {
        if (array_key_exists('emptyOption', $options)) {
            $list = ['' => $options['emptyOption']] + $list;
        }

        $selectOptions = false;
        if (array_key_exists('selectOptions', $options)) {
            $selectOptions = $options['selectOptions'] === true;
            unset($options['selectOptions']);
        }

        // When building a select box the "value" attribute is really the selected one
        // so we will use that when checking the model or session for a value which
        // should provide a convenient method of re-populating the forms on post.
        $selected = $this->getValueAttribute($name, $selected);

        $options['id'] = $this->getIdAttribute($name, $options);

        if (!isset($options['name'])) {
            $options['name'] = $name;
        }

        // We will simply loop through the options and build an HTML value for each of
        // them until we have an array of HTML declarations. Then we will join them
        // all together into one single HTML element that can be put on the form.
        $html = [];

        foreach ($list as $value => $display) {
            $html[] = $this->getSelectOption($display, $value, $selected);
        }

        // Once we have all of this HTML, we can join this into a single element after
        // formatting the attributes into an HTML "attributes" string, then we will
        // build out a final select statement, which will contain all the values.
        $options = $this->html->attributes($options);

        $list = implode('', $html);

        return $selectOptions ? $list : "<select{$options}>{$list}</select>";
    }

    /**
     * selectOptions only renders the options inside a select.
     * @param  string  $name
     * @param  array   $list
     * @param  string  $selected
     * @param  array   $options
     * @return string
     */
    public function selectOptions($name, $list = [], $selected = null, $options = [])
    {
        return $this->select($name, $list, $selected, ['selectOptions' => true] + $options);
    }

    /**
     * selectRange field.
     * @param  string  $name
     * @param  string  $begin
     * @param  string  $end
     * @param  string  $selected
     * @param  array   $options
     * @return string
     */
    public function selectRange($name, $begin, $end, $selected = null, $options = [])
    {
        $range = array_combine($range = range($begin, $end), $range);

        return $this->select($name, $range, $selected, $options);
    }

    /**
     * selectYear field.
     * @param  string  $name
     * @param  string  $begin
     * @param  string  $end
     * @param  string  $selected
     * @param  array   $options
     * @return string
     */
    public function selectYear()
    {
        return call_user_func_array([$this, 'selectRange'], func_get_args());
    }

    /**
     * selectMonth field.
     * @param  string  $name
     * @param  string  $selected
     * @param  array   $options
     * @param  string  $format
     * @return string
     */
    public function selectMonth($name, $selected = null, $options = [], $format = '%B')
    {
        $months = [];

        foreach (range(1, 12) as $month) {
            $months[$month] = strftime($format, mktime(0, 0, 0, $month, 1));
        }

        return $this->select($name, $months, $selected, $options);
    }

    /**
     * getSelectOption for the given value.
     * @param  string  $display
     * @param  string  $value
     * @param  string  $selected
     * @return string
     */
    public function getSelectOption($display, $value, $selected)
    {
        if (is_array($display)) {
            return $this->optionGroup($display, $value, $selected);
        }

        return $this->option($display, $value, $selected);
    }

    /**
     * optionGroup form element.
     * @param  array   $list
     * @param  string  $label
     * @param  string  $selected
     * @return string
     */
    protected function optionGroup($list, $label, $selected)
    {
        $html = [];

        foreach ($list as $value => $display) {
            $html[] = $this->option($display, $value, $selected);
        }

        return '<optgroup label="'.e($label).'">'.implode('', $html).'</optgroup>';
    }

    /**
     * option for a select element option.
     * @param  string  $display
     * @param  string  $value
     * @param  string  $selected
     * @return string
     */
    protected function option($display, $value, $selected)
    {
        $selected = $this->getSelectedValue($value, $selected);

        $options = ['value' => e($value), 'selected' => $selected];

        return '<option'.$this->html->attributes($options).'>'.e($display).'</option>';
    }

    /**
     * getSelectedValue determines if the value is selected.
     * @param  string  $value
     * @param  string  $selected
     * @return string
     */
    protected function getSelectedValue($value, $selected)
    {
        if (is_array($selected)) {
            return in_array($value, $selected) ? 'selected' : null;
        }

        return ((string) $value === (string) $selected) ? 'selected' : null;
    }

    //
    // Checkbox
    //

    /**
     * checkbox input field.
     * @param  string  $name
     * @param  mixed   $value
     * @param  bool    $checked
     * @param  array   $options
     * @return string
     */
    public function checkbox($name, $value = 1, $checked = null, $options = [])
    {
        return $this->checkable('checkbox', $name, $value, $checked, $options);
    }

    /**
     * radio button input field.
     * @param  string  $name
     * @param  mixed   $value
     * @param  bool    $checked
     * @param  array   $options
     * @return string
     */
    public function radio($name, $value = null, $checked = null, $options = [])
    {
        if (is_null($value)) {
            $value = $name;
        }

        return $this->checkable('radio', $name, $value, $checked, $options);
    }

    /**
     * checkable input field.
     * @param  string  $type
     * @param  string  $name
     * @param  mixed   $value
     * @param  bool    $checked
     * @param  array   $options
     * @return string
     */
    protected function checkable($type, $name, $value, $checked, $options)
    {
        $checked = $this->getCheckedState($type, $name, $value, $checked);

        if ($checked) {
            $options['checked'] = 'checked';
        }

        return $this->input($type, $name, $value, $options);
    }

    /**
     * getCheckedState for a checkable input.
     * @param  string  $type
     * @param  string  $name
     * @param  mixed   $value
     * @param  bool    $checked
     * @return bool
     */
    protected function getCheckedState($type, $name, $value, $checked)
    {
        switch ($type) {
            case 'checkbox':
                return $this->getCheckboxCheckedState($name, $value, $checked);

            case 'radio':
                return $this->getRadioCheckedState($name, $value, $checked);

            default:
                return $this->getValueAttribute($name) === $value;
        }
    }

    /**
     * getCheckboxCheckedState for a checkbox input.
     * @param  string  $name
     * @param  mixed  $value
     * @param  bool  $checked
     * @return bool
     */
    protected function getCheckboxCheckedState($name, $value, $checked)
    {
        if (
            isset($this->session) &&
            !$this->oldInputIsEmpty() &&
            is_null($this->old($name))
        ) {
            return false;
        }

        if ($this->missingOldAndModel($name)) {
            return $checked;
        }

        $posted = $this->getValueAttribute($name);

        return is_array($posted) ? in_array($value, $posted) : (bool) $posted;
    }

    /**
     * getRadioCheckedState for a radio input.
     * @param  string  $name
     * @param  mixed  $value
     * @param  bool  $checked
     * @return bool
     */
    protected function getRadioCheckedState($name, $value, $checked)
    {
        if ($this->missingOldAndModel($name)) {
            return $checked;
        }

        return $this->getValueAttribute($name) === $value;
    }

    /**
     * missingOldAndModel determines if old input or model input exists for a key.
     * @param  string  $name
     * @return bool
     */
    protected function missingOldAndModel($name)
    {
        return (is_null($this->old($name)) && is_null($this->getModelValueAttribute($name)));
    }

    /**
     * reset input element.
     * @param  string  $value
     * @param  array   $attributes
     * @return string
     */
    public function reset($value, $attributes = [])
    {
        return $this->input('reset', null, $value, $attributes);
    }

    /**
     * image input element.
     * @param  string  $url
     * @param  string  $name
     * @param  array   $attributes
     * @return string
     */
    public function image($url, $name = null, $attributes = [])
    {
        $attributes['src'] = $this->url->asset($url);

        return $this->input('image', $name, null, $attributes);
    }

    /**
     * submit button element.
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function submit($value = null, $options = [])
    {
        return $this->input('submit', null, $value, $options);
    }

    /**
     * button element.
     * @param  string  $value
     * @param  array   $options
     * @return string
     */
    public function button($value = null, $options = [])
    {
        if (!array_key_exists('type', $options)) {
            $options['type'] = 'button';
        }

        return '<button'.$this->html->attributes($options).'>'.$value.'</button>';
    }

    /**
     * getMethod parses the form action method.
     * @param  string  $method
     * @return string
     */
    protected function getMethod($method)
    {
        $method = strtoupper($method);

        return $method !== 'GET' ? 'POST' : $method;
    }

    /**
     * getAction gets the form action from the options.
     * @param  array   $options
     * @return string
     */
    protected function getAction(array $options)
    {
        // We will also check for a "route" or "action" parameter on the array so that
        // developers can easily specify a route or controller action when creating
        // a form providing a convenient interface for creating the form actions.
        if (isset($options['url'])) {
            return $this->getUrlAction($options['url']);
        }

        if (isset($options['route'])) {
            return $this->getRouteAction($options['route']);
        }

        // If an action is available, we are attempting to open a form to a controller
        // action route. So, we will use the URL generator to get the path to these
        // actions and return them from the method. Otherwise, we'll use current.
        elseif (isset($options['action'])) {
            return $this->getControllerAction($options['action']);
        }

        return $this->url->current();
    }

    /**
     * getUrlAction gets the action for a "url" option.
     * @param  array|string  $options
     * @return string
     */
    protected function getUrlAction($options)
    {
        if (is_array($options)) {
            return $this->url->to($options[0], array_slice($options, 1));
        }

        return $this->url->to($options);
    }

    /**
     * getRouteAction gets the action for a "route" option.
     * @param  array|string  $options
     * @return string
     */
    protected function getRouteAction($options)
    {
        if (is_array($options)) {
            return $this->url->route($options[0], array_slice($options, 1));
        }

        return $this->url->route($options);
    }

    /**
     * getControllerAction gets the action for an "action" option.
     * @param  array|string  $options
     * @return string
     */
    protected function getControllerAction($options)
    {
        if (is_array($options)) {
            return $this->url->action($options[0], array_slice($options, 1));
        }

        return $this->url->action($options);
    }

    /**
     * getAppendage gets the form appendage for the given method.
     * @param  string  $method
     * @return string
     */
    protected function getAppendage($method)
    {
        list($method, $appendage) = [strtoupper($method), ''];

        // If the HTTP method is in this list of spoofed methods, we will attach the
        // method spoofer hidden input to the form. This allows us to use regular
        // form to initiate PUT and DELETE requests in addition to the typical.
        if (in_array($method, $this->spoofedMethods)) {
            $appendage .= $this->hidden('_method', $method);
        }

        // If the method is something other than GET we will go ahead and attach the
        // CSRF token to the form, as this can't hurt and is convenient to simply
        // always have available on every form the developers creates for them.
        if ($method !== 'GET') {
            $appendage .= $this->token();
        }

        return $appendage;
    }

    /**
     * getIdAttribute for a field name.
     * @param  string  $name
     * @param  array   $attributes
     * @return string
     */
    public function getIdAttribute($name, $attributes)
    {
        if (array_key_exists('id', $attributes)) {
            return $attributes['id'];
        }

        if (in_array($name, $this->labels)) {
            return $name;
        }
    }

    /**
     * getValueAttribute that should be assigned to the field.
     * @param  string  $name
     * @param  string  $value
     * @return string
     */
    public function getValueAttribute($name, $value = null)
    {
        if (is_null($name)) {
            return $value;
        }

        if (!is_null($this->old($name))) {
            return $this->old($name);
        }

        if (!is_null($value)) {
            return $value;
        }

        if (isset($this->model)) {
            return $this->getModelValueAttribute($name);
        }
    }

    /**
     * getModelValueAttribute that should be assigned to the field.
     * @param  string  $name
     * @return string
     */
    protected function getModelValueAttribute($name)
    {
        if (is_object($this->model)) {
            return object_get($this->model, $this->transformKey($name));
        }
        elseif (is_array($this->model)) {
            return array_get($this->model, $this->transformKey($name));
        }
    }

    /**
     * old gets a value from the session's old input.
     * @param  string  $name
     * @return string
     */
    public function old($name)
    {
        if (isset($this->session)) {
            return $this->session->getOldInput($this->transformKey($name));
        }
    }

    /**
     * oldInputIsEmpty determines if the old input is empty.
     * @return bool
     */
    public function oldInputIsEmpty()
    {
        return (isset($this->session) && count($this->session->getOldInput()) === 0);
    }

    /**
     * transformKey from array to dot syntax.
     * @param  string  $key
     * @return string
     */
    protected function transformKey($key)
    {
        return str_replace(['.', '[]', '[', ']'], ['_', '', '.', ''], $key);
    }

    /**
     * getSessionStore implementation.
     * @return  \Illuminate\Session\Store  $session
     */
    public function getSessionStore()
    {
        return $this->session;
    }

    /**
     * setSessionStore implementation.
     * @param  \Illuminate\Session\Store  $session
     * @return $this
     */
    public function setSessionStore(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * value is a helper for getting form values. Tries to find the old value,
     * then uses a postback/get value, then looks at the form model values.
     * @param  string $name
     * @param  string $value
     * @return string
     */
    public function value($name, $value = null)
    {
        if (is_null($name)) {
            return $value;
        }

        if (!is_null($this->old($name))) {
            return $this->old($name);
        }

        if (!is_null(input($name, null))) {
            return input($name);
        }

        if (isset($this->model)) {
            return $this->getModelValueAttribute($name);
        }

        return $value;
    }

    /**
     * requestHandler returns a hidden HTML input, supplying the session key value.
     * @return string
     */
    protected function requestHandler($name = null)
    {
        if (!strlen($name)) {
            return '';
        }

        return $this->hidden('_handler', $name);
    }

    /**
     * sessionKey returns a hidden HTML input, supplying the session key value.
     * @return string
     */
    public function sessionKey($sessionKey = null)
    {
        if (!$sessionKey) {
            $sessionKey = post('_session_key', $this->sessionKey);
        }

        return $this->hidden('_session_key', $sessionKey);
    }

    /**
     * getSessionKey returns the active session key, used fr deferred bindings.
     * @return string
     */
    public function getSessionKey()
    {
        return $this->sessionKey;
    }
}
