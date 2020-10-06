<?php namespace October\Rain\Database\Traits;

use Exception;
use October\Rain\Database\SortableScope;

/**
 * SortableRelation model trait
 *
 * Usage:
 *
 * In the model class definition add:
 *
 *   use \October\Rain\Database\Traits\SortableRelation;
 *
 *   public $sortableRelations = ['relation_name' => 'sort_order_column'];
 *
 * To set orders:
 *
 *   $model->setSortableRelationOrder($relationName, $recordIds, $recordOrders);
 *
 */
trait SortableRelation
{
    /**
     * @var array The array of all sortable relations with their sort_order pivot column.
     *
     * public $sortableRelations = ['related_model' => 'sort_order'];
     */

    /**
     * Boot the SortableRelation trait for this model.
     * Make sure to add the sort_order value if a related model has been attached.
     * @return void
     */
    public function initializeSortableRelation()
    {
        $this->bindEvent('model.relation.afterAttach', function ($relationName, $attached, $data) {
            if (array_key_exists($relationName, $this->getSortableRelations())) {
                $column = $this->getRelationSortOrderColumn($relationName);

                $order = $this->$relationName()->max($column);

                foreach ($attached as $id) {
                    $order++;
                    $this->$relationName()->updateExistingPivot($id, [$column => $order]);
                }
            }
        });

        // Make sure all defined sortable relations load the sort_order column as pivot data.
        foreach ($this->getSortableRelations() as $relationName => $column) {
            $definition = $this->getRelationDefinition($relationName);
            $pivot = array_wrap(array_get($definition, 'pivot', []));

            if (!in_array($column, $pivot)) {
                $pivot[] = $column;
                $definition['pivot'] = $pivot;

                $relationType = $this->getRelationType($relationName);
                $this->$relationType[$relationName] = $definition;
            }
        }
    }

    /**
     * Sets the sort order of records to the specified orders. If the orders is
     * undefined, the record identifier is used.
     * @param  string $relation
     * @param  mixed  $itemIds
     * @param  array  $itemOrders
     * @return void
     */
    public function setRelationOrder($relationName, $itemIds, $itemOrders = null)
    {
        if (!is_array($itemIds)) {
            $itemIds = [$itemIds];
        }

        if ($itemOrders === null) {
            $itemOrders = $itemIds;
        }

        if (count($itemIds) != count($itemOrders)) {
            throw new Exception('Invalid setRelationOrder call - count of itemIds do not match count of itemOrders');
        }

        foreach ($itemIds as $index => $id) {
            $order = $itemOrders[$index];

            $this->$relationName()->updateExistingPivot($id, [
                $this->getRelationSortOrderColumn($relationName) => (int)$order
            ]);
        }
    }

    /**
     * Get the name of the "sort_order" column.
     * @param string $relation
     * @return string
     */
    public function getRelationSortOrderColumn($relation)
    {
        return $this->getSortableRelations()[$relation] ?? 'sort_order';
    }

    /**
     * Returns all configured sortable relations.
     * @return array
     */
    protected function getSortableRelations()
    {
        if (property_exists($this, 'sortableRelations')) {
            return $this->sortableRelations;
        }
        return [];
    }
}
