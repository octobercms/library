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
