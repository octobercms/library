<?php namespace October\Rain\Assetic\Asset;


use October\Rain\Assetic\Filter\FilterCollection;
use October\Rain\Assetic\Filter\FilterInterface;

/**
 * A base abstract asset.
 *
 * The methods load() and getLastModified() are left undefined, although a
 * reusable doLoad() method is available to child classes.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
abstract class BaseAsset implements AssetInterface
{
    /**
     * @var mixed filters
     */
    protected $filters;

    /**
     * @var mixed sourceRoot
     */
    protected $sourceRoot;

    /**
     * @var mixed sourcePath
     */
    protected $sourcePath;

    /**
     * @var mixed sourceDir
     */
    protected $sourceDir;

    /**
     * @var mixed targetPath
     */
    protected $targetPath;

    /**
     * @var mixed content
     */
    protected $content;

    /**
     * @var mixed loaded
     */
    protected $loaded;

    /**
     * @var mixed vars
     */
    protected $vars;

    /**
     * @var mixed values
     */
    protected $values;

    /**
     * __construct
     *
     * @param array $filters Filters for the asset
     * @param string $sourceRoot The root directory
     * @param string $sourcePath The asset path
     * @param array $vars
     */
    public function __construct($filters = array(), $sourceRoot = null, $sourcePath = null, array $vars = array())
    {
        $this->filters = new FilterCollection($filters);
        $this->sourceRoot = $sourceRoot;
        $this->sourcePath = $sourcePath;
        if ($sourcePath && $sourceRoot) {
            $this->sourceDir = dirname("$sourceRoot/$sourcePath");
        }
        $this->vars = $vars;
        $this->values = array();
        $this->loaded = false;
    }

    public function __clone()
    {
        $this->filters = clone $this->filters;
    }

    public function ensureFilter(FilterInterface $filter)
    {
        $this->filters->ensure($filter);
    }

    public function getFilters()
    {
        return $this->filters->all();
    }

    public function clearFilters()
    {
        $this->filters->clear();
    }

    /**
     * Encapsulates asset loading logic.
     *
     * @param string $content The asset content
     * @param FilterInterface $additionalFilter An additional filter
     */
    protected function doLoad($content, FilterInterface $additionalFilter = null)
    {
        $filter = clone $this->filters;
        if ($additionalFilter) {
            $filter->ensure($additionalFilter);
        }

        $asset = clone $this;
        $asset->setContent($content);

        $filter->filterLoad($asset);
        $this->content = $asset->getContent();

        $this->loaded = true;
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
        if (!$this->loaded) {
            $this->load();
        }

        $filter = clone $this->filters;
        if ($additionalFilter) {
            $filter->ensure($additionalFilter);
        }

        $asset = clone $this;
        $filter->filterDump($asset);

        return $asset->getContent();
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getSourceRoot()
    {
        return $this->sourceRoot;
    }

    public function getSourcePath()
    {
        return $this->sourcePath;
    }

    public function getSourceDirectory()
    {
        return $this->sourceDir;
    }

    public function getTargetPath()
    {
        return $this->targetPath;
    }

    public function setTargetPath($targetPath)
    {
        if ($this->vars) {
            foreach ($this->vars as $var) {
                if (false === strpos($targetPath, $var)) {
                    throw new \RuntimeException(sprintf('The asset target path "%s" must contain the variable "{%s}".', $targetPath, $var));
                }
            }
        }

        $this->targetPath = $targetPath;
    }

    public function getVars()
    {
        return $this->vars;
    }

    public function setValues(array $values)
    {
        foreach ($values as $var => $v) {
            if (!in_array($var, $this->vars, true)) {
                throw new \InvalidArgumentException(sprintf('The asset with source path "%s" has no variable named "%s".', $this->sourcePath, $var));
            }
        }

        $this->values = $values;
        $this->loaded = false;
    }

    public function getValues()
    {
        return $this->values;
    }
}
