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
     * loadNamespaceOverrides loads a local namespaced translation group for overrides
     */
    protected function loadNamespaceOverrides(array $lines, $locale, $group, $namespace)
    {
        $namespace = str_replace('.', '/', $namespace);
        $file = "{$this->path}/{$locale}/{$namespace}/{$group}.php";

        if ($this->files->exists($file)) {
            return array_replace_recursive($lines, $this->files->getRequire($file));
        }

        return $lines;
    }
}
