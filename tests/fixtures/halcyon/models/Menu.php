<?php

use October\Rain\Halcyon\Model;

class HalcyonTestMenu extends Model
{

    /**
     * @var bool Model supports code and settings sections.
     */
    protected $isCompoundObject = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'content',
    ];

    /**
     * The container name associated with the model, eg: pages.
     *
     * @var string
     */
    protected $dirName = 'menus';
}
