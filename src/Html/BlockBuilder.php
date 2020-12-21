<?php namespace October\Rain\Html;

use Exception;

/**
 * Block manager
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class BlockBuilder
{
    protected $blockStack = [];
    protected $blocks = [];

    /**
     * Helper for startBlock
     *
     * @param string $name Specifies the block name.
     * @return void
     */
    public function put($name)
    {
        $this->startBlock($name);
    }

    /**
     * Begins the layout block.
     *
     * This method enables output buffering, so all output will be captured as a part of this block.
     *
     * @param string $name Specifies the block name.
     * @return void
     */
    public function startBlock($name)
    {
        array_push($this->blockStack, $name);
        ob_start();
    }

    /**
     * Helper for endBlock and also clears the output buffer.
     *
     * @param boolean $append Indicates that the new content should be appended to the existing block content.
     * @return void
     * @throws \Exception if there are no items in the block stack
     */
    public function endPut($append = false)
    {
        $this->endBlock($append);
    }

    /**
     * Closes the layout block.
     *
     * This captures all buffered output as the block's content, and ends output buffering.
     *
     * @param boolean $append Indicates that the new content should be appended to the existing block content.
     * @return void
     * @throws \Exception if there are no items in the block stack
     */
    public function endBlock($append = false)
    {
        if (!count($this->blockStack)) {
            throw new Exception('Invalid block nesting');
        }

        $name = array_pop($this->blockStack);
        $contents = ob_get_clean();

        if ($append) {
            $this->append($name, $contents);
        } else {
            $this->blocks[$name] = $contents;
        }
    }

    /**
     * Sets a content of the layout block.
     *
     * Output buffering is not used for this method.
     *
     * @param string $name Specifies the block name.
     * @param string $content Specifies the block content.
     * @return void
     * @throws \Exception if there are no items in the block stack
     */
    public function set($name, $content)
    {
        $this->blocks[$name] = $content;
    }

    /**
     * Appends a content of the layout block.
     *
     * Output buffering is not used for this method.
     *
     * @param string $name Specifies the block name.
     * @param string $content Specifies the block content.
     * @return void
     */
    public function append($name, $content)
    {
        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = '';
        }

        $this->blocks[$name] .= $content;
    }

    /**
     * Returns the layout block contents and deletes the block from memory.
     *
     * @param string $name Specifies the block name.
     * @param string $default Specifies a default block value to use if the block requested is not exists.
     * @return string
     */
    public function placeholder($name, $default = null)
    {
        $result = $this->get($name, $default);
        unset($this->blocks[$name]);

        if (is_string($result)) {
            $result = trim($result);
        }

        return $result;
    }

    /**
     * Returns the layout block contents but not deletes the block from memory.
     *
     * @param string $name Specifies the block name.
     * @param string $default Specifies a default block value to use if the block requested is not exists.
     * @return string
     */
    public function get($name, $default = null)
    {
        if (!isset($this->blocks[$name])) {
            return  $default;
        }

        return $this->blocks[$name];
    }

    /**
     * Clears all the registered blocks.
     *
     * @return void
     */
    public function reset()
    {
        $this->blockStack = [];
        $this->blocks = [];
    }

    /**
     * Gets the block stack at this point.
     *
     * @return array
     */
    public function getBlockStack()
    {
        return $this->blockStack;
    }
}
