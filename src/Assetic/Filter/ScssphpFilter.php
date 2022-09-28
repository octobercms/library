<?php namespace October\Rain\Assetic\Filter;

use Url;
use File;
use Config;
use Storage;
use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Factory\AssetFactory;
use October\Rain\Assetic\Util\SassUtils;
use ScssPhp\ScssPhp\Compiler;

/**
 * ScssphpFilter loads SCSS files using the PHP implementation of scss, scssphp.
 *
 * Scss files are mostly compatible, but there are slight differences.
 *
 * @link https://github.com/scssphp/scssphp
 *
 * @author Bart van den Burg <bart@samson-it.nl>
 */
class ScssphpFilter implements DependencyExtractorInterface
{
    private $importPaths = [];
    private $customFunctions = [];
    private $formatter;
    private $variables = [];

    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
    }

    public function setVariables(array $variables)
    {
        $this->variables = $variables;
    }

    public function addVariable($variable)
    {
        $this->variables[] = $variable;
    }

    public function setImportPaths(array $paths)
    {
        $this->importPaths = $paths;
    }

    public function addImportPath($path)
    {
        $this->importPaths[] = $path;
    }

    public function registerFunction($name, $callable)
    {
        $this->customFunctions[$name] = $callable;
    }

    public function filterLoad(AssetInterface $asset)
    {
        $sc = new Compiler();

        if ($dir = $asset->getSourceDirectory()) {
            $sc->addImportPath($dir);
        }

        foreach ($this->importPaths as $path) {
            $sc->addImportPath($path);
        }

        foreach ($this->customFunctions as $name => $callable) {
            $sc->registerFunction($name, $callable);
        }

        if ($this->formatter) {
            $sc->setOutputStyle($this->formatter);
        }

        if (!empty($this->variables)) {
            $sc->addVariables($this->variables);
        }

        // Generate source map file
        $useSourceMaps = Config::get('cms.enable_asset_source_maps', false);
        if ($useSourceMaps) {
            $mapFile = md5($asset->getSourcePath()).'.css.map';

            $sc->setSourceMap(Compiler::SOURCE_MAP_FILE);
            $sc->setSourceMapOptions([
                'sourceMapURL' => $this->getSourceMapPublicUrl().'/'.$mapFile,
                'sourceMapBasepath' => '',
                'sourceRoot' => '/',
            ]);

            $result = $sc->compileString($asset->getContent());
            File::put($this->getSourceMapLocalPath().'/'.$mapFile, $result->getSourceMap());
        }
        else {
            $result = $sc->compileString($asset->getContent());
        }

        $asset->setContent($result->getCss());
    }

    public function filterDump(AssetInterface $asset)
    {
    }

    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $sc = new Compiler();
        if ($loadPath !== null) {
            $sc->addImportPath($loadPath);
        }

        foreach ($this->importPaths as $path) {
            $sc->addImportPath($path);
        }

        $children = [];
        foreach (SassUtils::extractImports($content) as $match) {
            $file = $sc->findImport($match);
            if ($file) {
                $children[] = $child = $factory->createAsset($file, [], ['root' => $loadPath]);
                $child->load();
                $children = array_merge(
                    $children,
                    $this->getChildren($factory, $child->getContent(), $child->getSourceDirectory())
                );
            }
        }

        return $children;
    }

    /**
     * getSourceMapLocalPath returns the local path for source maps
     */
    protected function getSourceMapLocalPath(): string
    {
        $path = rtrim(Config::get('filesystems.disks.resources.root', storage_path('app/resources')), '/');
        $path .= '/sourcemap';

        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true, true);
        }

        return $path;
    }

    /**
     * getSourceMapPublicUrl returns the public address for the source map path
     */
    protected function getSourceMapPublicUrl(): string
    {
        $fullPath = Config::get('filesystems.disks.resources.url', '/storage/app/resources');
        $fullPath .= '/sourcemap';

        if (
            Config::get('filesystems.disks.resources.driver') === 'local' &&
            Config::get('system.relative_links') === true
        ) {
            return Url::toRelative($fullPath);
        }

        return Url::asset($fullPath);
    }
}
