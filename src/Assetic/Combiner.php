<?php namespace October\Rain\Assetic;

use October\Rain\Assetic\Asset\FileAsset;
use October\Rain\Assetic\Asset\AssetCache;
use October\Rain\Assetic\Asset\AssetCollection;
use October\Rain\Assetic\Cache\FilesystemCache;
use File;

/**
 * Combiner helper class
 *
 * @package october/assetic
 * @author Alexey Bobkov, Samuel Georges
 */
class Combiner
{
    use \October\Rain\Assetic\Traits\HasDeepHasher;

    /**
     * @var string storagePath is the output folder for storing combined files.
     */
    protected $storagePath;

    /**
     * @var string localPath is the local path context to find assets.
     */
    protected $localPath;

    /**
     * @var array filters to apply to each file.
     */
    protected $filters = [];

    /**
     * @var array prodFilters filters to apply in production
     */
    protected $prodFilters = [];

    /**
     * parse
     */
    public function parse(array $assets, $options = [])
    {
        return $this->prepareCombiner($assets, $options)->dump();
    }

    /**
     * prepareCombiner before dumping
     */
    public function prepareCombiner(array $assets, $options = [])
    {
        extract(array_merge([
            'targetPath' => null,
            'production' => false,
            'useCache' => true,
            'deepHashKey' => null
        ], $options));

        if ($deepHashKey !== null) {
            $this->setDeepHashKeyOnFilters($deepHashKey);
        }

        $files = [];
        $filesSalt = null;
        foreach ($assets as $asset) {
            $filters = $this->getFilters(File::extension($asset), $production);
            $path = file_exists($asset)
                ? $asset
                : (File::symbolizePath($asset, null) ?: $this->localPath . $asset);

            $files[] = new FileAsset($path, $filters, base_path());
            $filesSalt .= $this->localPath . $asset;
        }
        $filesSalt = md5($filesSalt);

        $collection = new AssetCollection($files, [], $filesSalt);
        $collection->setTargetPath($targetPath);

        if (!$useCache || $this->storagePath === null) {
            return $collection;
        }

        if (!File::isDirectory($this->storagePath)) {
            @File::makeDirectory($this->storagePath);
        }

        $cache = new FilesystemCache($this->storagePath);

        $cachedFiles = [];
        foreach ($files as $file) {
            $cachedFiles[] = new AssetCache($file, $cache);
        }

        $cachedCollection = new AssetCollection($cachedFiles, [], $filesSalt);
        $cachedCollection->setTargetPath($targetPath);

        return $cachedCollection;
    }

    /**
     * registerDefaultFilters
     */
    public function registerDefaultFilters()
    {
        // Default JavaScript filters
        $this->registerFilter('js', new \October\Rain\Assetic\Filter\JavascriptImporter);

        // Default StyleSheet filters
        $this->registerFilter('css', new \October\Rain\Assetic\Filter\CssImportFilter);
        $this->registerFilter(['css', 'less', 'scss'], new \October\Rain\Assetic\Filter\CssRewriteFilter);
        $this->registerFilter('less', new \October\Rain\Assetic\Filter\LessCompiler);
        $this->registerFilter('scss', new \October\Rain\Assetic\Filter\ScssCompiler);

        // Production filters
        $this->registerFilter('js', new \October\Rain\Assetic\Filter\JSMinFilter, true);
        $this->registerFilter(['css', 'less', 'scss'], new \October\Rain\Assetic\Filter\StylesheetMinify, true);
    }

    /**
     * setStoragePath
     */
    public function setStoragePath($path)
    {
        $this->storagePath = $path;
    }

    /**
     * setLocalPath
     */
    public function setLocalPath($path)
    {
        $this->localPath = $path;
    }

    //
    // Filters
    //

    /**
     * registerFilter to apply to the combining process.
     * @param string|array $extension Extension name. Eg: css
     * @param object $filter Collection of files to combine.
     * @param bool $isProduction
     * @return self
     */
    public function registerFilter($extension, $filter, $isProduction = false)
    {
        if (is_array($extension)) {
            foreach ($extension as $_extension) {
                $this->registerFilter($_extension, $filter);
            }
            return;
        }

        $extension = strtolower($extension);
        $destination = $isProduction ? 'prodFilters' : 'filters';

        if (!isset($this->$destination[$extension])) {
            $this->$destination[$extension] = [];
        }

        if ($filter !== null) {
            $this->$destination[$extension][] = $filter;
        }

        return $this;
    }

    /**
     * resetFilters clears any registered filters.
     * @param string $extension Extension name. Eg: css
     * @return self
     */
    public function resetFilters($extension = null)
    {
        if ($extension === null) {
            $this->filters = [];
            $this->prodFilters = [];
        }
        else {
            $this->filters[$extension] = [];
            $this->prodFilters[$extension] = [];
        }

        return $this;
    }

    /**
     * getFilters returns all defined filters for a given extension
     */
    public function getFilters(string $extension = null, $isProduction = false): array
    {
        if ($isProduction) {
            if ($extension === null) {
                return array_merge($this->filters, $this->prodFilters);
            }

            return array_merge(
                ($this->filters[$extension] ?? []),
                ($this->prodFilters[$extension] ?? [])
            );
        }

        if ($extension === null) {
            return $this->filters;
        }

        return $this->filters[$extension] ?? [];
    }
}
