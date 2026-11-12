<?php namespace October\Rain\Assetic\Filter;

use Url;
use File;
use Config;
use Storage;
use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Factory\AssetFactory;
use October\Rain\Assetic\Util\SassUtils;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use ScssPhp\ScssPhp\ValueConverter;
use ScssPhp\ScssPhp\Value\Value;
use ScssPhp\ScssPhp\Value\SassString;
use ScssPhp\ScssPhp\Exception\SassException;

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
    /**
     * @var array importPaths
     */
    protected $importPaths = [];

    /**
     * @var array customFunctions
     */
    protected $customFunctions = [];

    /**
     * @var mixed formatter
     */
    protected $formatter;

    /**
     * @var array variables
     */
    protected $variables = [];

    /**
     * setFormatter
     */
    public function setFormatter($formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * setVariables
     */
    public function setVariables(array $variables)
    {
        $this->variables = $variables;
    }

    /**
     * addVariable
     */
    public function addVariable($variable)
    {
        $this->variables[] = $variable;
    }

    /**
     * setImportPaths
     */
    public function setImportPaths(array $paths)
    {
        $this->importPaths = $paths;
    }

    /**
     * addImportPath
     */
    public function addImportPath($path)
    {
        $this->importPaths[] = $path;
    }

    /**
     * registerFunction adds a custom Sass function, where omitting the argument declaration passes all positional arguments as one array.
     */
    public function registerFunction($name, $callable, ?array $argumentDeclaration = null)
    {
        if ($argumentDeclaration === null) {
            $argumentDeclaration = ['args...'];
            $callable = function (array $args) use ($callable) {
                return $callable($args[0][2]);
            };
        }

        $this->customFunctions[$name] = [$callable, $argumentDeclaration];
    }

    /**
     * filterLoad
     */
    public function filterLoad(AssetInterface $asset): void
    {
        $sc = new Compiler();

        if ($dir = $asset->getSourceDirectory()) {
            $sc->addImportPath($dir);
        }

        foreach ($this->importPaths as $path) {
            $sc->addImportPath($path);
        }

        foreach ($this->customFunctions as $name => [$callable, $argumentDeclaration]) {
            $sc->registerFunction($name, $callable, $argumentDeclaration);
        }

        if ($this->formatter) {
            $sc->setOutputStyle($this->formatter instanceof OutputStyle
                ? $this->formatter
                : OutputStyle::fromString($this->formatter));
        }

        if (!empty($this->variables)) {
            $sc->addVariables(array_map([$this, 'makeSassValue'], $this->variables));
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

    /**
     * filterDump
     */
    public function filterDump(AssetInterface $asset): void
    {
    }

    /**
     * getChildren
     */
    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $importPaths = $this->importPaths;
        if ($loadPath !== null) {
            array_unshift($importPaths, $loadPath);
        }

        $children = [];
        foreach (SassUtils::extractImports($content) as $match) {
            $file = $this->findImport($match, $importPaths);
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
     * findImport returns the file an import resolves to, skipping plain CSS and external imports.
     */
    protected function findImport(string $url, array $importPaths): ?string
    {
        if (preg_match('~\.css$|^https?://|^//~', $url)) {
            return null;
        }

        foreach ($importPaths as $dir) {
            $file = null;
            if (is_string($dir)) {
                $file = $this->resolveImportPath($dir.'/'.$url);
            }
            elseif (is_callable($dir)) {
                $file = $dir($url);
            }

            if (!empty($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * resolveImportPath fills in the partial prefix, extension and directory index using the Sass lookup order.
     */
    protected function resolveImportPath(string $path): ?string
    {
        if (in_array(pathinfo($path, PATHINFO_EXTENSION), ['sass', 'scss', 'css'])) {
            $candidates = [$path];
        }
        else {
            $candidates = [
                $path.'.sass',
                $path.'.scss',
                $path.'.css',
                $path.'/index.sass',
                $path.'/index.scss',
                $path.'/index.css',
            ];
        }

        foreach ($candidates as $candidate) {
            $partial = dirname($candidate).'/_'.basename($candidate);
            if (is_file($partial)) {
                return $partial;
            }

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * makeSassValue converts a variable to a Sass value, treating strings that fail to parse as unquoted text.
     */
    protected function makeSassValue($value): Value
    {
        if (!is_string($value)) {
            return ValueConverter::fromPhp($value);
        }

        if (trim($value) === '') {
            return new SassString($value, false);
        }

        try {
            return ValueConverter::parseValue($value);
        }
        catch (SassException $ex) {
            return new SassString($value, false);
        }
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
