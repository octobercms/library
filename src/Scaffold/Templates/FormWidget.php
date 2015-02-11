<?php namespace October\Rain\Scaffold\Templates;

use October\Rain\Scaffold\TemplateBase;

class FormWidget extends TemplateBase
{
    /**
     * @var array A mapping of stub to generated file.
     */
    protected $fileMap = [
        'formwidget/formwidget.stub'      => 'formwidgets/{{studly_name}}.php',
        'formwidget/partial.stub'         => 'formwidgets/{{lower_name}}/partials/_{{lower_name}}.htm',
        'formwidget/stylesheet.stub'      => 'formwidgets/{{lower_name}}/assets/css/{{lower_name}}.css',
        'formwidget/javascript.stub'      => 'formwidgets/{{lower_name}}/assets/js/{{lower_name}}.js',
    ];
}