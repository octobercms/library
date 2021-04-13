<?php namespace October\Rain\Database\Models;

use October\Rain\Database\Model;

/**
 * Revision Model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Revision extends Model
{
    /**
     * @var string table associated with the model
     */
    public $table = 'revisions';

    /**
     * getNewValueAttribute returns "new value" casted as the saved type
     */
    public function getNewValueAttribute($value)
    {
        if ($this->cast === 'date') {
            return $this->asDateTime($value);
        }

        return $value;
    }

    /**
     * getOldValueAttribute returns "old value" casted as the saved type
     */
    public function getOldValueAttribute($value)
    {
        if ($this->cast === 'date') {
            return $this->asDateTime($value);
        }

        return $value;
    }
}
