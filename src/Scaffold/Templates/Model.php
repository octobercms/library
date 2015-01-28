<?php namespace October\Rain\Scaffold\Templates;

use October\Rain\Scaffold\TemplateBase;

class Model extends TemplateBase
{
    /**
     * @var array A mapping of stub to generated file.
     */
    protected $fileMap = [
        'model/model.stub'        => 'models/{{studly_name}}.php',
        'model/fields.stub'       => 'models/{{lower_name}}/fields.yaml',
        'model/columns.stub'      => 'models/{{lower_name}}/columns.yaml',
        'model/create_table.stub' => 'updates/create_{{snake_plural_name}}_table.php',
    ];
}