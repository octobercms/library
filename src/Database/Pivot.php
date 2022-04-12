<?php namespace October\Rain\Database;

/**
 * Pivot model base class
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Pivot extends Model
{
    use \Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
}
