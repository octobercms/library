<?php namespace October\Rain\Database\Models;

use October\Rain\Database\Model;

/**
 * TranslateAttribute stores translated attribute values for translatable models
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class TranslateAttribute extends Model
{
    /**
     * @var string table associated with the model
     */
    public $table = 'translate_attributes';

    /**
     * @var bool timestamps
     */
    public $timestamps = false;

    /**
     * @var array fillable fields
     */
    protected $fillable = ['locale', 'attribute', 'value'];

    /**
     * @var array morphTo
     */
    public $morphTo = [
        'model' => []
    ];
}
