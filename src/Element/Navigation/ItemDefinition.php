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
     * @var array config in raw format, if supplied.
     */
    public $config;

    /**
     * evalConfig from an array and apply them to the object
     */
    protected function evalConfig(array $config): void
    {
        $this->code = $config['code'] ?? $this->code;
        $this->label = $config['label'] ?? $this->label;
        $this->url = $config['url'] ?? $this->url;
        $this->icon = $config['icon'] ?? $this->icon;
        $this->order = $config['order'] ?? $this->order;
        $this->customData = $config['customData'] ?? $this->customData;
    }

    /**
     * useConfig
     */
    public function useConfig(array $config): ItemDefinition
    {
        $this->config = $config;

        $this->evalConfig($config);

        return $this;
    }

    /**
     * label for this item
     */
    public function label(string $label): ItemDefinition
    {
        $this->label = $label;

        return $this;
    }

    /**
     * url for this item
     */
    public function url(string $url): ItemDefinition
    {
        $this->url = $url;

        return $this;
    }

    /**
     * icon for this item
     */
    public function icon(string $icon): ItemDefinition
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * order for this item
     */
    public function order(int $order): ItemDefinition
    {
        $this->order = $order;

        return $this;
    }
}
