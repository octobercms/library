<?php namespace October\Rain\Database\Behaviors;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * Sortable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Sortable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Sortable;

    public function bootSortable()
    {
        $model = $this->model;

        $model->created(function($model) {
            $sortOrderColumn = $model->getSortOrderColumn();
            if (is_null($model->$sortOrderColumn)) {
                $model->setSortableOrder($model->getKey());
            }
        });
        $model->addGlobalScope(new SortableScope);
    }
}
