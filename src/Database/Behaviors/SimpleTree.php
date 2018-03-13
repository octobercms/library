<?php namespace October\Rain\Database\Behaviors;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * SimpleTree trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class SimpleTree extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\SimpleTree;

    public function bootSimpleTree()
    {
        $model = $this->model;
        /*
         * Define relationships
         */
        $model->hasMany['children'] = [
            get_class($model),
            'key' => $model->getParentColumnName()
        ];

        $model->belongsTo['parent'] = [
            get_class($model),
            'key' => $model->getParentColumnName()
        ];
    }
}
