<?php

use October\Rain\Halcyon\Model;

class HalcyonTestPage extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'viewBag',
        'markup',
        'code'
    ];

    /**
     * The container name associated with the model, eg: pages.
     *
     * @var string
     */
    protected $dirName = 'pages';

}

class HalcyonTestPageWithValidation extends HalcyonTestPage
{
    use \October\Rain\Halcyon\Traits\Validation;

    public $customMessages = [
       'required' => 'The :attribute field is required.'
    ];

    public $attributeNames = [
       'title' => 'title',
       'viewBag.meta_title' => 'meta title'
    ];

    public $rules = [
        'title' => 'required',
        'viewBag.meta_title' => 'required'
    ];
}
