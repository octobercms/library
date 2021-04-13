<?php namespace October\Rain\Database\Attach;

use October\Rain\Resize\Resizer as ResizerBase;

/**
 * Resizer for images
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 * @deprecated
 * @see \October\Rain\Resize\Resizer
 */
class Resizer extends ResizerBase
{
    public function __construct($file)
    {
        traceLog('October\Rain\Database\Attach\Resizer is deprecated, use October\Rain\Resize\Resizer instead');

        parent::__construct($file);
    }
}
