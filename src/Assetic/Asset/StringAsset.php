<?php namespace October\Rain\Assetic\Asset;

use October\Rain\Assetic\Filter\FilterInterface;

/**
 * StringAsset represents a string asset.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class StringAsset extends BaseAsset
{
    /**
     * @var string string
     */
    private $string;

    /**
     * @var int|null lastModified
     */
    private $lastModified;

    /**
     * __construct
     *
     * @param string $content    The content of the asset
     * @param array  $filters    Filters for the asset
     * @param string $sourceRoot The source asset root directory
     * @param string $sourcePath The source asset path
     */
    public function __construct(string $content, array $filters = [], ?string $sourceRoot = null, ?string $sourcePath = null)
    {
        $this->string = $content;

        parent::__construct($filters, $sourceRoot, $sourcePath);
    }

    /**
     * load
     */
    public function load(?FilterInterface $additionalFilter = null): void
    {
        $this->doLoad($this->string, $additionalFilter);
    }

    /**
     * setLastModified
     */
    public function setLastModified(?int $lastModified): void
    {
        $this->lastModified = $lastModified;
    }

    /**
     * getLastModified
     */
    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }
}
