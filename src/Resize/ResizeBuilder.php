<?php namespace October\Rain\Resize;

/**
 * ResizeBuilder builds resizers on demand
 *
 * @package october\resize
 * @author Alexey Bobkov, Samuel Georges
 */
class ResizeBuilder
{
    public function open($filename)
    {
        return new Resizer($filename);
    }
}
