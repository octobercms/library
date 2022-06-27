<?php namespace October\Rain\Assetic\Factory;

use October\Rain\Assetic\Asset\AssetCollection;
use October\Rain\Assetic\Asset\AssetCollectionInterface;
use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Asset\FileAsset;
use October\Rain\Assetic\Asset\GlobAsset;
use October\Rain\Assetic\Asset\HttpAsset;
use October\Rain\Assetic\AssetManager;
use October\Rain\Assetic\Filter\DependencyExtractorInterface;
use October\Rain\Assetic\FilterManager;
use LogicException;

/**
 * AssetFactory creates asset objects.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetFactory
{
    /**
     * @var mixed root
     */
    protected $root;

    /**
     * @var mixed debug
     */
    protected $debug;

    /**
     * @var mixed output
     */
    protected $output;

    /**
     * @var mixed am
     */
    protected $am;

    /**
     * @var mixed fm
     */
    protected $fm;

    /**
     * __construct
     *
     * @param string  $root  The default root directory
     * @param Boolean $debug Filters prefixed with a "?" will be omitted in debug mode
     */
    public function __construct($root, $debug = false)
    {
        $this->root = rtrim($root, '/');
        $this->debug = $debug;
        $this->output = 'assetic/*';
    }

    /**
     * Sets debug mode for the current factory.
     *
     * @param Boolean $debug Debug mode
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Checks if the factory is in debug mode.
     *
     * @return Boolean Debug mode
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Sets the default output string.
     *
     * @param string $output The default output string
     */
    public function setDefaultOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Returns the current asset manager.
     *
     * @return AssetManager|null The asset manager
     */
    public function getAssetManager()
    {
        return $this->am;
    }

    /**
     * Sets the asset manager to use when creating asset references.
     *
     * @param AssetManager $am The asset manager
     */
    public function setAssetManager(AssetManager $am)
    {
        $this->am = $am;
    }

    /**
     * Returns the current filter manager.
     *
     * @return FilterManager|null The filter manager
     */
    public function getFilterManager()
    {
        return $this->fm;
    }

    /**
     * Sets the filter manager to use when adding filters.
     *
     * @param FilterManager $fm The filter manager
     */
    public function setFilterManager(FilterManager $fm)
    {
        $this->fm = $fm;
    }

    /**
     * createAsset creates a new asset.
     *
     * Prefixing a filter name with a question mark will cause it to be
     * omitted when the factory is in debug mode.
     *
     * Available options:
     *
     *  * output: An output string
     *  * name:   An asset name for interpolation in output patterns
     *  * debug:  Forces debug mode on or off for this asset
     *  * root:   An array or string of more root directories
     *
     * @param array|string $inputs  An array of input strings
     * @param array|string $filters An array of filter names
     * @param array        $options An array of options
     *
     * @return AssetCollection An asset collection
     */
    public function createAsset($inputs = [], $filters = [], array $options = [])
    {
        if (!is_array($inputs)) {
            $inputs = array($inputs);
        }

        if (!is_array($filters)) {
            $filters = array($filters);
        }

        if (!isset($options['output'])) {
            $options['output'] = $this->output;
        }

        if (!isset($options['vars'])) {
            $options['vars'] = [];
        }

        if (!isset($options['debug'])) {
            $options['debug'] = $this->debug;
        }

        if (!isset($options['root'])) {
            $options['root'] = array($this->root);
        }
        else {
            if (!is_array($options['root'])) {
                $options['root'] = array($options['root']);
            }

            $options['root'][] = $this->root;
        }

        if (!isset($options['name'])) {
            $options['name'] = $this->generateAssetName($inputs, $filters, $options);
        }

        $asset = $this->createAssetCollection([], $options);
        $extensions = [];

        // inner assets
        foreach ($inputs as $input) {
            if (is_array($input)) {
                // nested formula
                $asset->add(call_user_func_array(array($this, 'createAsset'), $input));
            }
            else {
                $asset->add($this->parseInput($input, $options));
                $extensions[pathinfo($input, PATHINFO_EXTENSION)] = true;
            }
        }

        // filters
        foreach ($filters as $filter) {
            if ('?' != $filter[0]) {
                $asset->ensureFilter($this->getFilter($filter));
            }
            elseif (!$options['debug']) {
                $asset->ensureFilter($this->getFilter(substr($filter, 1)));
            }
        }

        // append variables
        if (!empty($options['vars'])) {
            $toAdd = [];
            foreach ($options['vars'] as $var) {
                if (false !== strpos($options['output'], '{'.$var.'}')) {
                    continue;
                }

                $toAdd[] = '{'.$var.'}';
            }

            if ($toAdd) {
                $options['output'] = str_replace('*', '*.'.implode('.', $toAdd), $options['output']);
            }
        }

        // append consensus extension if missing
        if (1 == count($extensions) && !pathinfo($options['output'], PATHINFO_EXTENSION) && $extension = key($extensions)) {
            $options['output'] .= '.'.$extension;
        }

        // output --> target url
        $asset->setTargetPath(str_replace('*', $options['name'], $options['output']));

        // Return as a collection
        return $asset instanceof AssetCollectionInterface
            ? $asset
            : $this->createAssetCollection([$asset]);
    }

    /**
     * generateAssetName
     */
    public function generateAssetName($inputs, $filters, $options = [])
    {
        foreach (array_diff(array_keys($options), array('output', 'debug', 'root')) as $key) {
            unset($options[$key]);
        }

        ksort($options);

        return substr(sha1(serialize($inputs).serialize($filters).serialize($options)), 0, 7);
    }

    /**
     * getLastModified
     */
    public function getLastModified(AssetInterface $asset)
    {
        $mtime = 0;
        foreach ($asset instanceof AssetCollectionInterface ? $asset : array($asset) as $leaf) {
            $mtime = max($mtime, $leaf->getLastModified());

            if (!$filters = $leaf->getFilters()) {
                continue;
            }

            $prevFilters = [];
            foreach ($filters as $filter) {
                $prevFilters[] = $filter;

                if (!$filter instanceof DependencyExtractorInterface) {
                    continue;
                }

                // extract children from leaf after running all preceeding filters
                $clone = clone $leaf;
                $clone->clearFilters();
                foreach (array_slice($prevFilters, 0, -1) as $prevFilter) {
                    $clone->ensureFilter($prevFilter);
                }
                $clone->load();

                foreach ($filter->getChildren($this, $clone->getContent(), $clone->getSourceDirectory()) as $child) {
                    $mtime = max($mtime, $this->getLastModified($child));
                }
            }
        }

        return $mtime;
    }

    /**
     * Parses an input string string into an asset.
     *
     * The input string can be one of the following:
     *
     *  * An absolute URL: If the string contains "://" or starts with "//" it will be interpreted as an HTTP asset
     *  * A glob:          If the string contains a "*" it will be interpreted as a glob
     *  * A path:          Otherwise the string is interpreted as a filesystem path
     *
     * Both globs and paths will be absolutized using the current root directory.
     *
     * @param string $input   An input string
     * @param array  $options An array of options
     *
     * @return AssetInterface An asset
     */
    protected function parseInput($input, array $options = [])
    {
        if (false !== strpos($input, '://') || 0 === strpos($input, '//')) {
            return $this->createHttpAsset($input, $options['vars']);
        }
        if (self::isAbsolutePath($input)) {
            if ($root = self::findRootDir($input, $options['root'])) {
                $path = ltrim(substr($input, strlen($root)), '/');
            }
            else {
                $path = null;
            }
        }
        else {
            $root  = $this->root;
            $path  = $input;
            $input = $this->root.'/'.$path;
        }

        if (false !== strpos($input, '*')) {
            return $this->createGlobAsset($input, $root, $options['vars']);
        }

        return $this->createFileAsset($input, $root, $path, $options['vars']);
    }

    /**
     * createAssetCollection
     */
    protected function createAssetCollection(array $assets = [], array $options = [])
    {
        return new AssetCollection($assets, [], null, isset($options['vars']) ? $options['vars'] : []);
    }

    /**
     * createHttpAsset
     */
    protected function createHttpAsset($sourceUrl, $vars)
    {
        return new HttpAsset($sourceUrl, [], false, $vars);
    }

    /**
     * createGlobAsset
     */
    protected function createGlobAsset($glob, $root = null, $vars = [])
    {
        return new GlobAsset($glob, [], $root, $vars);
    }

    /**
     * createFileAsset
     */
    protected function createFileAsset($source, $root = null, $path = null, $vars = [])
    {
        return new FileAsset($source, [], $root, $path, $vars);
    }

    /**
     * getFilter
     */
    protected function getFilter($name)
    {
        if (!$this->fm) {
            throw new LogicException('There is no filter manager.');
        }

        return $this->fm->get($name);
    }

    /**
     * isAbsolutePath
     */
    private static function isAbsolutePath($path)
    {
        return '/' == $path[0] || '\\' == $path[0] || (3 < strlen($path) && ctype_alpha($path[0]) && $path[1] == ':' && ('\\' == $path[2] || '/' == $path[2]));
    }

    /**
     * Loops through the root directories and returns the first match.
     *
     * @param string $path  An absolute path
     * @param array  $roots An array of root directories
     *
     * @return string|null The matching root directory, if found
     */
    private static function findRootDir($path, array $roots)
    {
        foreach ($roots as $root) {
            if (0 === strpos($path, $root)) {
                return $root;
            }
        }
    }
}
