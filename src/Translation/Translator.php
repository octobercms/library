<?php namespace October\Rain\Translation;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Translation\Translator as TranslatorBase;

/**
 * Translator class
 *
 * @package october/translation
 * @author Alexey Bobkov, Samuel Georges
 */
class Translator extends TranslatorBase
{
    /**
     * @var string CORE_LOCALE is the native system language
     */
    const CORE_LOCALE = 'en';

    /**
     * @var \October\Rain\Events\Dispatcher events dispatcher instance
     */
    protected $events;

    /**
     * get the translation for the given key.
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        /**
         * @event translator.beforeResolve
         * Fires before the translator resolves the requested language key
         *
         * Example usage (overrides the value returned for a specific language key):
         *
         *     Event::listen('translator.beforeResolve', function ((string) $key, (array) $replace, (string|null) $locale) {
         *         if ($key === 'my.custom.key') {
         *             return 'My overriding value';
         *         }
         *     });
         *
         */
        if (
            isset($this->events) &&
            ($line = $this->events->fire('translator.beforeResolve', [$key, $replace, $locale], true))
        ) {
            return $line;
        }

        if ($line = $this->getValidationSpecific($key, $replace, $locale)) {
            return $line;
        }

        return parent::get($key, $replace, $locale, $fallback);
    }

    /**
     * getValidationSpecific checks the system namespace by default for "validation" keys
     */
    protected function getValidationSpecific($key, $replace, $locale)
    {
        if (
            starts_with($key, 'validation.') &&
            !starts_with($key, 'validation.custom.') &&
            !starts_with($key, 'validation.attributes.')
        ) {
            $nativeKey = 'system::'.$key;
            $line = $this->get($nativeKey, $replace, $locale);
            if ($line !== $nativeKey) {
                return $line;
            }
        }

        return null;
    }

    /**
     * trans returns the translation for a given key
     *
     * @param  array|string  $id
     * @param  array   $parameters
     * @param  string  $locale
     * @return string
     */
    public function trans($id, array $parameters = [], $locale = null)
    {
        return $this->get($id, $parameters, $locale);
    }

    /**
     * transChoice gets a translation according to an integer value
     *
     * @param  string  $id
     * @param  int     $number
     * @param  array   $parameters
     * @param  string  $locale
     * @return string
     */
    public function transChoice($id, $number, array $parameters = [], $locale = null)
    {
        return $this->choice($id, $number, $parameters, $locale);
    }

    /**
     * localeArray gets the array of locales to be checked
     *
     * @param  string|null  $locale
     * @return array
     */
    protected function localeArray($locale)
    {
        return array_filter([$locale ?: $this->locale, $this->fallback, static::CORE_LOCALE]);
    }

    /**
     * setEventDispatcher instance
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }
}
