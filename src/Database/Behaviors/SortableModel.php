<?php namespace October\Rain\Database\Behaviors;

use Exception;
use October\Rain\Database\ModelBehavior;

/**
 * Sortable model extension
 *
 * Usage:
 *
 * Model table must have sort_order table column.
 * In the model class definition: 
 *
 *   public $implement = ['October.Rain.Database.Behaviors.SortableModel'];
 *
 * To set orders: 
 *
 *   $obj->setSortableOrder($recordIds, $recordOrders);
 *
 * You can change the sort field used by declaring:
 *
 *   public $sortableModelField = 'my_sort_order';
 *
 */
class SortableModel extends ModelBehavior
{

    /**
     * @var string The database column that identifies the sort order.
     */
    protected $fieldName = 'sort_order';

    /*
     * Constructor
     */
    public function __construct($model)
    {
        parent::__construct($model);

        if (isset($this->model->sortableModelField))
            $this->fieldName = $this->model->sortableModelField;

        $model->bind('model.created', function() use ($model) {
            $this->setSortableOrder($model->id);
        });
    }

    /**
     * Sets the sort order of records to the specified orders. If the orders is
     * undefined, the record identifier is used.
     */
    public function setSortableOrder($itemIds, $itemOrders = null)
    {
        if (!is_array($itemIds))
            $itemIds = [$itemIds];

        if ($itemOrders === null)
            $itemOrders = $itemIds;

        if (count($itemIds) != count($itemOrders))
            throw new Exception('Invalid setSortableOrder call - count of itemIds do not match count of itemOrders');

        foreach ($itemIds as $index => $id) {
            $order = $itemOrders[$index];
            $this->model->newQuery()->where('id', $id)->update([$this->fieldName => $order]);
        }
    }

}