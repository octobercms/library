<?php namespace October\Rain\Scaffold\Templates;

use October\Rain\Scaffold\TemplateBase;

class Component extends TemplateBase
{
    /**
     * @var array A mapping of stub to generated file.
     */
    protected $fileMap = [
        'component/component.stub'  => 'components/{{studly_name}}.php',
        'component/default.stub' => 'components/{{lower_name}}/default.htm',
    ];
}