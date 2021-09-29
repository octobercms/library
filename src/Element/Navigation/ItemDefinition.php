<?php namespace October\Rain\Element\Navigation;

/**
 * ItemDefinition
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class ItemDefinition
{
    /**
     * @var string code
     */
    public $code;

    /**
     * @var string label
     */
    public $label;

    /**
     * @var string url
     */
    public $url;

    /**
     * @var null|string icon
     */
    public $icon;

    /**
     * @var int order
     */
    public $order = -1;

    /**
     * @var array customData
     */
    public $customData = [];

    /**
     * useConfig
     */
    public function useConfig(array $data): ItemDefinition
    {
        $this->code = $data['code'] ?? $this->code;
        $this->label = $data['label'] ?? $this->label;
        $this->url = $data['url'] ?? $this->url;
        $this->icon = $data['icon'] ?? $this->icon;
        $this->order = $data['order'] ?? $this->order;
        $this->customData = $data['customData'] ?? $this->customData;

        return $this;
    }
}
