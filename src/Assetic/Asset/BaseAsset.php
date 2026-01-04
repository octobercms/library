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
     * @var FilterCollection filters
     */
    protected $filters;

    /**
     * @var string|null sourceRoot
     */
    protected $sourceRoot;

    /**
     * @var string|null sourcePath
     */
    protected $sourcePath;

    /**
     * @var string|null sourceDir
     */
    protected $sourceDir;

    /**
     * @var string|null targetPath
     */
    protected $targetPath;

    /**
     * @var string|null content
     */
    protected $content;

    /**
     * @var bool loaded
     */
    protected $loaded;

    /**
     * @var array vars
     */
    protected $vars;

    /**
     * @var array values
     */
    protected $values;

    /**
     * __construct
     *
     * @param array  $filters    Filters for the asset
     * @param string $sourceRoot The root directory
     * @param string $sourcePath The asset path
     * @param array  $vars
     */
    public function __construct(array $filters = [], ?string $sourceRoot = null, ?string $sourcePath = null, array $vars = [])
    {
        $this->filters = new FilterCollection($filters);
        $this->sourceRoot = $sourceRoot;
        $this->sourcePath = $sourcePath;
        if ($sourcePath && $sourceRoot) {
            $this->sourceDir = dirname("$sourceRoot/$sourcePath");
        }
        $this->vars = $vars;
        $this->values = [];
        $this->loaded = false;
    }

    public function __clone()
    {
        $this->filters = clone $this->filters;
    }

    public function ensureFilter(FilterInterface $filter): void
    {
        $this->filters->ensure($filter);
    }

    public function getFilters(): array
    {
        return $this->filters->all();
    }

    public function clearFilters(): void
    {
        $this->filters->clear();
    }

    /**
     * Encapsulates asset loading logic.
     *
     * @param string          $content          The asset content
     * @param FilterInterface $additionalFilter An additional filter
     */
    protected function doLoad(?string $content, ?FilterInterface $additionalFilter = null): void
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

    public function dump(?FilterInterface $additionalFilter = null): string
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

        return $asset->getContent() ?? '';
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getSourceRoot(): ?string
    {
        return $this->sourceRoot;
    }

    public function getSourcePath(): ?string
    {
        return $this->sourcePath;
    }

    public function getSourceDirectory(): ?string
    {
        return $this->sourceDir;
    }

    public function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    public function setTargetPath(?string $targetPath): void
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

    public function getVars(): array
    {
        return $this->vars;
    }

    public function setValues(array $values): void
    {
        foreach ($values as $var => $v) {
            if (!in_array($var, $this->vars, true)) {
                throw new \InvalidArgumentException(sprintf('The asset with source path "%s" has no variable named "%s".', $this->sourcePath, $var));
            }
        }

        $this->values = $values;
        $this->loaded = false;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
