<?php namespace October\Rain\Boilerplate\Templates;

use October\Rain\Boilerplate\Base;

class Model extends Base
{
    /**
     * @var array A mapping of stub to generated file.
     */
    protected $fileMap = [
        'model/model.stub' => '{{studly_name}}.php',
        'model/form_fields.stub' => '{{lower_name}}/form_fields.yaml',
        'model/list_columns.stub' => '{{lower_name}}/list_columns.yaml',
    ];
}