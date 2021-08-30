<?php namespace October\Rain\Database\Models;

use Model;

/**
 * Draft record
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Draft extends Model
{
    /**
     * @var string table associated with the model
     */
    protected $table = 'drafts';

    /**
     * @var array fillable attributes that are mass assignable
     */
    protected $fillable = [
        'name',
        'notes',
    ];
}
