<?php namespace October\Rain\Translation;

use Illuminate\Translation\Translator as IlluminateTranslator;
use Illuminate\Translation\LoaderInterface;
use Illuminate\Translation\FileLoader;
use Illuminate\Filesystem\Filesystem;

/**
 * October translator class.
 *
 * @package translation
 * @author Alexey Bobkov, Samuel Georges
 */
class Translator extends IlluminateTranslator
{
    protected $appTranslator;

    /**
     * Create a new translator instance.
     * @param  \Illuminate\Translation\LoaderInterface  $loader
     * @param  string  $locale
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(LoaderInterface $loader, $locale, $fallbackLocale, Filesystem $files)
    {
        parent::__construct($loader, $locale);
        parent::setFallback($fallbackLocale);

        $appLoader = new FileLoader($files, app_path().'/lang');
        $this->appTranslator = new IlluminateTranslator($appLoader, $locale);
    }

    /**
     * Add a new namespace to the loader.
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        parent::addNamespace($namespace, $hint);

        $namespaceAppPath = app_path().'/lang/'.str_replace('.', '/', $namespace);
        $this->appTranslator->addNamespace($namespace, $namespaceAppPath);
    }

    /**
     * Get the translation for the given key.
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function get($key, array $replace = array(), $locale = null)
    {
        $nativeKey = $key;

        if (starts_with($nativeKey, 'validation.') && !starts_with($nativeKey, 'validation.custom.') && !starts_with($nativeKey, 'validation.attributes.'))
            $nativeKey = 'system::'.$key;

        $value = parent::get($nativeKey, $replace, $locale);
        if (strpos($nativeKey, '::') === false)
            return $value;

        $appValue = $this->appTranslator->get($key, $replace, $locale);

        return $appValue == $key ? $value : $appValue;
    }

}