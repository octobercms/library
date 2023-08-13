<?php namespace October\Rain\Translation;

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
     * get the translation for the given key.
     */
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        if ($line = $this->getValidationSpecific($key, $replace, $locale)) {
            return $line;
        }

        return parent::get($key, $replace, $locale, $fallback);
    }

    /**
     * set a given language key value.
     *
     * @param array|string $key
     * @param mixed $value
     * @param string|null $locale
     * @return void
     */
    public function set($key, $value = null, $locale = null)
    {
        if (is_array($key)) {
            foreach ($key as $innerKey => $innerValue) {
                $this->set($innerKey, $innerValue, $locale);
            }
        } else {
            $locale = $locale ?: $this->locale;

            $this->loaded['*']['*'][$locale][$key] = $value;
        }
    }

    /**
     * getValidationSpecific checks the system namespace by default for "validation" keys
     */
    protected function getValidationSpecific($key, $replace, $locale)
    {
        if (
            str_starts_with($key, 'validation.') &&
            !str_starts_with($key, 'validation.custom.') &&
            !str_starts_with($key, 'validation.attributes.')
        ) {
            $nativeKey = 'system::' . $key;
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
     * @param array|string $id
     * @param array $parameters
     * @param string $locale
     * @return string
     */
    public function trans($id, array $parameters = [], $locale = null)
    {
        return $this->get($id, $parameters, $locale);
    }

    /**
     * transChoice gets a translation according to an integer value
     *
     * @param string $id
     * @param int $number
     * @param array $parameters
     * @param string $locale
     * @return string
     */
    public function transChoice($id, $number, array $parameters = [], $locale = null)
    {
        return $this->choice($id, $number, $parameters, $locale);
    }

    /**
     * localeArray gets the array of locales to be checked
     *
     * @param string|null $locale
     * @return array
     */
    protected function localeArray($locale)
    {
        return array_filter([$locale ?: $this->locale, $this->fallback, static::CORE_LOCALE]);
    }
}
