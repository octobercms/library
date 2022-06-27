<?php namespace October\Rain\Assetic\Asset;

use October\Rain\Assetic\Filter\FilterInterface;
use October\Rain\Assetic\Util\VarUtils;
use Traversable;

/**
 * GlobAsset is a collection of assets loaded by glob.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class GlobAsset extends AssetCollection
{
    /**
     * @var mixed globs
     */
    protected $globs;

    /**
     * @var mixed initialized
     */
    protected $initialized;

    /**
     * __construct
     *
     * @param string|array $globs   A single glob path or array of paths
     * @param array        $filters An array of filters
     * @param string       $root    The root directory
     * @param array        $vars
     */
    public function __construct($globs, $filters = [], $root = null, array $vars = [])
    {
        $this->globs = (array) $globs;
        $this->initialized = false;

        parent::__construct([], $filters, $root, $vars);
    }

    /**
     * all
     */
    public function all()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::all();
    }

    /**
     * load
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        parent::load($additionalFilter);
    }

    /**
     * dump
     */
    public function dump(FilterInterface $additionalFilter = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::dump($additionalFilter);
    }

    /**
     * getLastModified
     */
    public function getLastModified()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::getLastModified();
    }

    /**
     * getIterator
     */
    public function getIterator(): Traversable
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::getIterator();
    }

    /**
     * setValues
     */
    public function setValues(array $values)
    {
        parent::setValues($values);
        $this->initialized = false;
    }

    /**
     * initialize the collection based on the glob(s) passed in.
     */
    private function initialize()
    {
        foreach ($this->globs as $glob) {
            $glob = VarUtils::resolve($glob, $this->getVars(), $this->getValues());

            if (false !== $paths = glob($glob)) {
                foreach ($paths as $path) {
                    if (is_file($path)) {
                        $asset = new FileAsset($path, [], $this->getSourceRoot(), null, $this->getVars());
                        $asset->setValues($this->getValues());
                        $this->add($asset);
                    }
                }
            }
        }

        $this->initialized = true;
    }
}
