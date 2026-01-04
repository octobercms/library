<?php namespace October\Rain\Assetic\Filter;


use October\Rain\Assetic\Asset\AssetInterface;

/**
 * JSqueeze filter.
 *
 * @link https://github.com/nicolas-grekas/JSqueeze
 * @author Nicolas Grekas <p@tchwork.com>
 */
class JSqueezeFilter implements FilterInterface
{
    /**
     * @var mixed singleLine
     */
    protected $singleLine = true;

    /**
     * @var bool keepImportantComments
     */
    protected $keepImportantComments = true;

    /**
     * @var mixed className
     */
    protected $className;

    /**
     * @var bool specialVarRx
     */
    protected $specialVarRx = false;

    /**
     * @var mixed defaultRx
     */
    protected $defaultRx;

    /**
     * __construct
     */
    public function __construct()
    {
        // JSqueeze is namespaced since 2.x, this works with both 1.x and 2.x
        if (class_exists('\\Patchwork\\JSqueeze')) {
            $this->className = '\\Patchwork\\JSqueeze';
            $this->defaultRx = \Patchwork\JSqueeze::SPECIAL_VAR_PACKER;
        } else {
            $this->className = '\\JSqueeze';
            $this->defaultRx = \JSqueeze::SPECIAL_VAR_RX;
        }
    }

    public function setSingleLine($bool)
    {
        $this->singleLine = (bool) $bool;
    }

    // call setSpecialVarRx(true) to enable global var/method/property
    // renaming with the default regex (for 1.x or 2.x)
    public function setSpecialVarRx($specialVarRx)
    {
        if (true === $specialVarRx) {
            $this->specialVarRx = $this->defaultRx;
        } else {
            $this->specialVarRx = $specialVarRx;
        }
    }

    public function keepImportantComments($bool)
    {
        $this->keepImportantComments = (bool) $bool;
    }

    public function filterLoad(AssetInterface $asset): void
    {
    }

    public function filterDump(AssetInterface $asset): void
    {
        $parser = new $this->className();
        $asset->setContent($parser->squeeze(
            $asset->getContent(),
            $this->singleLine,
            $this->keepImportantComments,
            $this->specialVarRx
        ));
    }
}
