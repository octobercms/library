<?php namespace October\Rain\Boilerplate\Templates;

use October\Rain\Boilerplate\TemplateBase;

class Controller extends TemplateBase
{
    /**
     * @var array A mapping of stub to generated file.
     */
    protected $fileMap = [
        'controller/_list_toolbar.stub' => 'controllers/{{lower_name}}/_list_toolbar.htm',
        'controller/create.stub'        => 'controllers/{{lower_name}}/create.htm',
        'controller/index.stub'         => 'controllers/{{lower_name}}/index.htm',
        'controller/preview.stub'       => 'controllers/{{lower_name}}/preview.htm',
        'controller/update.stub'        => 'controllers/{{lower_name}}/update.htm',
        'controller/form_config.stub'   => 'controllers/{{lower_name}}/form_config.yaml',
        'controller/list_config.stub'   => 'controllers/{{lower_name}}/list_config.yaml',
        'controller/controller.stub'    => 'controllers/{{studly_name}}.php',
    ];
}