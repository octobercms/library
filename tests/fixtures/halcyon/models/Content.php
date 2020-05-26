<?php

use October\Rain\Halcyon\Model;

class HalcyonTestContent extends Model
{
    /**
     * @var string The container name associated with the model, eg: pages.
     */
    protected $dirName = 'content';

    /**
     * @var array Allowable file extensions.
     */
    protected $allowedExtensions = ['htm', 'txt', 'md'];
}
