<?php namespace October\Rain\Extension;

/**
 * Extension trait
 *
 * Allows for "Private traits"
 *
 * @package october\extension
 * @author Alexey Bobkov, Samuel Georges
 */

trait ExtensionTrait
{
    protected $extensionHidden = array(
        'fields' => array(),
        'methods' => array('extensionIsHiddenField', 'extensionIsHiddenField')
    );

    protected function extensionHideField($name)
    {
        $this->extensionHidden['fields'][] = $name;
    }

    protected function extensionHideMethod($name)
    {
        $this->extensionHidden['methods'][] = $name;
    }

    public function extensionIsHiddenField($name)
    {
        return in_array($name, $this->extensionHidden['fields']);
    }

    public function extensionIsHiddenMethod($name)
    {
        return in_array($name, $this->extensionHidden['methods']);
    }
}