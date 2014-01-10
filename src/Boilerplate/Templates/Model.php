<?php namespace October\Rain\Boilerplate\Templates;

use October\Rain\Boilerplate\TemplateBase;

class Model extends TemplateBase
{
    /**
     * @var array A mapping of stub to generated file.
     */
    protected $fileMap = [
        'model/model.stub'        => 'models/{{studly_name}}.php',
        'model/form_fields.stub'  => 'models/{{lower_name}}/form_fields.yaml',
        'model/list_columns.stub' => 'models/{{lower_name}}/list_columns.yaml',
    ];
}