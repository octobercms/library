<?php

use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Filter\FilterInterface;

/**
 * Class MockAsset
 *
 * This class implements the AssetInterface and can be used
 * to test Assetic filters.
 */
class MockAsset implements AssetInterface
{
    public $content;

    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    public function ensureFilter(FilterInterface $filter)
    {
    }

    public function getFilters()
    {
    }

    public function clearFilters()
    {
    }

    public function load(FilterInterface $additionalFilter = null)
    {
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
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
    }

    public function getSourcePath()
    {
    }

    public function getSourceDirectory()
    {
    }

    public function getTargetPath()
    {
    }

    public function setTargetPath($targetPath)
    {
    }

    public function getLastModified()
    {
    }

    public function getVars()
    {
    }

    public function setValues(array $values)
    {
    }

    public function getValues()
    {
    }
}
