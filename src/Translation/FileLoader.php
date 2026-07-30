<?php namespace October\Rain\Translation;

use Illuminate\Translation\FileLoader as FileLoaderBase;

/**
 * FileLoader specifies a custom location for overriding translations
 *
 * @package october\translation
 * @author Alexey Bobkov, Samuel Georges
 */
class FileLoader extends FileLoaderBase
{
    /**
     * @var string path is a single path for the loader.
     *
     * @todo Can be removed if Laravel >= 10
     */
    protected $path;

    /**
     * @var array paths are used by default for the loader.
     *
     * @todo Can be removed if Laravel >= 10
     */
    protected $paths;

    /**
     * @var array jsonLines maps a locale to programmatically-registered JSON
     * translation lines
     */
    protected $jsonLines = [];

    /**
     * loadNamespaceOverrides loads a local namespaced translation group for overrides
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
        $paths = (array) $this->path ?: $this->paths;

        return collect($paths)
            ->reduce(function ($output, $path) use ($lines, $locale, $group, $namespace) {
                $namespace = str_replace('.', '/', $namespace);
                $file = "{$path}/{$namespace}/{$locale}/{$group}.php";

                if ($this->files->exists($file)) {
                    return array_replace_recursive($lines, $this->files->getRequire($file));
                }

                return $lines;
            }, []);
    }

    /**
     * addJsonLines registers JSON translation lines for a locale from a non-file source
     */
    public function addJsonLines(string $locale, array $lines): void
    {
        if (isset($this->jsonLines[$locale])) {
            $this->jsonLines[$locale] = array_merge($this->jsonLines[$locale], $lines);
            return;
        }

        $this->jsonLines[$locale] = $lines;
    }

    /**
     * loadJsonPaths returns the JSON translations for a locale
     */
    protected function loadJsonPaths($locale)
    {
        if (array_key_exists($locale, $this->jsonLines)) {
            return $this->jsonLines[$locale];
        }

        return parent::loadJsonPaths($locale);
    }
}
