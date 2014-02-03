<?php namespace October\Rain\Support;

use Illuminate\Support\MessageBag;

/**
 * Simple flash bag
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class FlashBag extends MessageBag
{

    const INFO = 'info';
    const ERROR = 'error';
    const SUCCESS = 'success';
    const WARNING = 'warning';

    public $type;

    public function __construct(array $messages = array(), $type = null)
    {
        $this->type = ($type) ?: self::INFO;
        parent::__construct($messages);
    }

    /**
     * Convert the message bag to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->first();
    }

}