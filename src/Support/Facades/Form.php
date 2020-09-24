<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static string open(array $options = [])
 * @method static string ajax(string $handler, array $options = [])
 * @method static string model(mixed $model, array $options = [])
 * @method static void setModel(mixed $model)
 * @method static string close()
 * @method static string token()
 * @method static string label(string $name, string $value = null, array $options = [])
 * @method static string input(string $type, string $name, string $value = null, array $options = [])
 * @method static string text(string $name, string $value, array $options = [])
 * @method static string password(string $name, array $options = [])
 * @method static string hidden(string $name, string $value = null, array $options = [])
 * @method static string email(string $name, string $value = null, array $options = [])
 * @method static string url(string $name, string $value = null, array $options = [])
 * @method static string file(string $name, array $options = [])
 * @method static string textarea(string $name, string $value = null, array $options = [])
 * @method static string select(string $name, array $list = [], string $value = null, array $options = [])
 * @method static string selectRange(string $name, string $begin, string $end, string $selected = null, array $options = [])
 * @method static string selectYear()
 * @method static string selectMonth(string $name, string $selected = null, array $options = [], string $format = '%B')
 * @method static string getSelectOption(string|array $display, string $value, string $selected)
 * @method static string checkbox(string $name, $value = 1, bool $checked = null, array $options = [])
 * @method static string radio(string $name, $value = null, bool $checked = null, array $options = [])
 * @method static string reset(string $value, array $attributes = [])
 * @method static string image(string $url, string $name = null, array $attributes = [])
 * @method static string button(string $value = null, array $options = [])
 * @method static string getIdAttribute(string $name, array $attributes)
 * @method static string getValueAttribute(string $name, string $value = null)
 * @method static string old(string $name)
 * @method static string bool oldInputIsEmpty()
 * @method static \Illuminate\Session\Store getSessionStore()
 * @method static \October\Rain\Html\FormBuilder setSessionStore(\Illuminate\Session\Store $session)
 * @method static string value(string $name, string $value = null)
 * @method static string sessionKey(string $sessionKey = null)
 * @method static string getSessionKey()
 *
 * @see \October\Rain\Html\FormBuilder
 */
class Form extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'form';
    }
}
