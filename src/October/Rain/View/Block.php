<?php namespace October\Rain\View;

/**
 * Block manager
 */
class Block
{
    private static $blockStack = array();
    private static $blocks = array();

    /**
     * Begins the layout block.
     * @param string $name Specifies the block name.
     */
    public static function put($name) 
    {
        array_push(self::$blockStack, $name);
        ob_start();
    }
    
    /**
     * Closes the layout block.
     * @param boolean $append Indicates that the new content should be appended to the existing block content.
     */
    public static function endPut($append = false) 
    {
        if (!count(self::$blockStack))
            throw new \Exception('Invalid block nesting');

        $name = array_pop(self::$blockStack);
        $contents = ob_get_clean();

        if (!isset(self::$blocks[$name]))
            self::$blocks[$name] = $contents;
        else 
            if ($append)
                self::$blocks[$name] .= $contents;

        if (!count(self::$blockStack) && (ob_get_length() > 0))
            ob_end_clean();
    }

    /**
     * Sets a content of the layout block.
     * @param string $name Specifies the block name.
     * @param string $content Specifies the block content.
     * 
     */
    public static function set($name, $content)
    {
        self::put($name);
        echo $content;
        self::endPut();
    }

    /**
     * Appends a content of the layout block.
     * @param string $name Specifies the block name.
     * @param string $content Specifies the block content.
     * 
     */
    public static function append($name, $content)
    {
        if (!isset(self::$blocks[$name]))
            self::$blocks[$name] = null;

        self::$blocks[$name] .= $content;
    }

    /**
     * Returns the layout block contents and deletes the block from memory.
     * @param string $name Specifies the block name.
     * @param string $default Specifies a default block value to use if the block requested is not exists.
     * @return string
     */
    public static function placeholder($name, $default = null)
    {
        $result = self::get($name, $default);
        unset(self::$blocks[$name]);
        return trim($result);
    }

    /**
     * Returns the layout block contents but not deletes the block from memory.
     * @param string $name Specifies the block name.
     * @param string $default Specifies a default block value to use if the block requested is not exists.
     * @return string
     */
    public static function get($name, $default = null)
    {
        if (!isset(self::$blocks[$name]))
            return  $default;

        $result = self::$blocks[$name];
        return $result;
    }

    public static function reset()
    {
        self::$blockStacks = array();
        self::$blocks = array();
    }

}