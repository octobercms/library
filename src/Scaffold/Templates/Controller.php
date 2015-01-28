<?php namespace October\Rain\Scaffold\Templates;

use October\Rain\Scaffold\TemplateBase;

class Controller extends TemplateBase
{
    /**
     * @var array A mapping of stub to generated file.
     */
    protected $fileMap = [
        'controller/_list_toolbar.stub' => 'controllers/{{lower_name}}/_list_toolbar.htm',
        'controller/config_form.stub'   => 'controllers/{{lower_name}}/config_form.yaml',
        'controller/config_list.stub'   => 'controllers/{{lower_name}}/config_list.yaml',
        'controller/create.stub'        => 'controllers/{{lower_name}}/create.htm',
        'controller/index.stub'         => 'controllers/{{lower_name}}/index.htm',
        'controller/preview.stub'       => 'controllers/{{lower_name}}/preview.htm',
        'controller/update.stub'        => 'controllers/{{lower_name}}/update.htm',
        'controller/controller.stub'    => 'controllers/{{studly_name}}.php',
    ];
}