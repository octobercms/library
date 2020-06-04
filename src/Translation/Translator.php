<?php namespace October\Rain\Translation;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Translation\Translator as TranslatorBase;

/**
 * October translator class.
 *
 * @package translation
 * @author Alexey Bobkov, Samuel Georges
 */
class Translator extends TranslatorBase
{
    use \October\Rain\Support\Traits\KeyParser;

    const CORE_LOCALE = 'en';

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher|\October\Rain\Events\Dispatcher
     */
    protected $events;

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array|null
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
        if (isset($this->events) &&
            ($line = $this->events->fire('translator.beforeResolve', [$key, $replace, $locale], true))) {
            return $line;
        }

        if ($line = $this->getValidationSpecific($key, $replace, $locale)) {
            return $line;
        }

        list($namespace, $group, $item) = $this->parseKey($key);

        if (is_null($namespace)) {
            $namespace = '*';
        }

        // Here we will get the locale that should be used for the language line. If one
        // was not passed, we will use the default locales which was given to us when
        // the translator was instantiated. Then, we can load the lines and return.
        foreach ($this->parseLocale($locale, $fallback) as $locale) {
            $line = $this->getLine(
                $namespace,
                $group,
                $locale,
                $item,
                $replace
            );

            if (!is_null($line)) {
                break;
            }
        }

        // If the line doesn't exist, we will return back the key which was requested as
        // that will be quick to spot in the UI if language keys are wrong or missing
        // from the application's language files. Otherwise we can return the line.
        if (!isset($line)) {
            return $this->makeReplacements($key, $replace);
        }

        return $line;
    }

    /**
     * Check the system namespace by default for "validation" keys.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string
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
     * Get a translation according to an integer value.
     *
     * @param  string  $key
     * @param  int|array|\Countable  $number
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function choice($key, $number, array $replace = [], $locale = null)
    {
        $line = $this->get(
            $key,
            $replace,
            $locale = $this->localeForChoice($locale)
        );

        // If the given "number" is actually an array or countable we will simply count the
        // number of elements in an instance. This allows developers to pass an array of
        // items without having to count it on their end first which gives bad syntax.
        if (is_array($number) || $number instanceof Countable) {
            $number = count($number);
        }

        // Format locale for MessageSelector
        if (strpos($locale, '-') !== false) {
            $localeParts = explode('-', $locale, 2);
            $locale = $localeParts[0] . '_' . strtoupper($localeParts[1]);
        }
        
        $replace['count'] = $number;

        return $this->makeReplacements($this->getSelector()->choose($line, $number, $locale), $replace);
    }

    /**
     * Get the array of locales to be checked.
     *
     * @param  string|null  $locale
     * @param  bool         $fallback
     * @return array
     */
    protected function parseLocale($locale, $fallback)
    {
        $locales = $fallback ? $this->localeArray($locale) : [$locale ?: $this->locale];

        $locales[] = static::CORE_LOCALE;

        return $locales;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }
}
