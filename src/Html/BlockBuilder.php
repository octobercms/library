<?php namespace October\Rain\Html;

use Exception;

/**
 * BlockBuilder is used for building placeholders and putting content to them
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class BlockBuilder
{
    /**
     * @var array blockStack
     */
    protected $blockStack = [];

    /**
     * @var array blocks
     */
    protected $blocks = [];

    /**
     * put is a helper for startBlock
     */
    public function put(string $name)
    {
        $this->startBlock($name);
    }

    /**
     * startBlock begins the layout block
     */
    public function startBlock(string $name)
    {
        array_push($this->blockStack, $name);
        ob_start();
    }

    /**
     * endPut is a helper for endBlock and also clears the output buffer
     * Append indicates that the new content should be appended to the existing block content
     */
    public function endPut(bool $append = false)
    {
        $this->endBlock($append);
    }

    /**
     * endBlock closes the layout block
     * Append indicates that the new content should be appended to the existing block content
     */
    public function endBlock(bool $append = false)
    {
        if (!count($this->blockStack)) {
            throw new Exception('Invalid block nesting');
        }

        $name = array_pop($this->blockStack);
        $contents = ob_get_clean();

        if ($append) {
            $this->append($name, $contents);
        }
        else {
            $this->blocks[$name] = $contents;
        }
    }

    /**
     * set a content of the layout block.
     */
    public function set(string $name, $content)
    {
        $this->blocks[$name] = $content;
    }

    /**
     * append a content of the layout block
     */
    public function append(string $name, $content)
    {
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = '';
        }

        $this->blocks[$name] .= $content;
    }

    /**
     * placeholder returns the layout block contents and deletes the block from memory.
     */
    public function placeholder(string $name, string $default = null): ?string
    {
        $result = $this->get($name, $default);
        unset($this->blocks[$name]);

        if (is_string($result)) {
            $result = trim($result);
        }

        return $result;
    }

    /**
     * get returns the layout block contents but not deletes the block from memory
     */
    public function get(string $name, string $default = null): ?string
    {
        if (!isset($this->blocks[$name])) {
            return $default;
        }

        return (string) $this->blocks[$name];
    }

    /**
     * reset clears all the registered blocks
     */
    public function reset()
    {
        $this->blockStack = [];
        $this->blocks = [];
    }
}
