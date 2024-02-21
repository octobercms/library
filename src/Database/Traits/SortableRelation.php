<?php namespace October\Rain\Database\Traits;

use Db;
use Exception;

/**
 * SortableRelation adds sorting support to pivot relationships
 *
 * Usage:
 *
 * In the model class definition add:
 *
 *   use \October\Rain\Database\Traits\SortableRelation;
 *
 *   public $belongsToMany = [..., 'pivotSortable' => 'sort_order_column'];
 *
 * To set orders:
 *
 *   $model->setSortableRelationOrder($relationName, $recordIds, $recordOrders);
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait SortableRelation
{
    /**
     * @var array sortableRelationDefinitions
     */
    protected $sortableRelationDefinitions;

    /**
     * initializeSortableRelation trait for the model.
     */
    public function initializeSortableRelation()
    {
        $this->bindEvent('model.afterInit', function() {
            $this->defineSortableRelations();
        });

        $this->bindEvent('model.relation.attach', function ($relationName, $attached, $data) {
            if (!array_key_exists($relationName, $this->getSortableRelations())) {
                return;
            }

            // Order already set in pivot data (assuming singular)
            $column = $this->getRelationSortOrderColumn($relationName);
            if (is_array($data) && array_key_exists($column, $data)) {
                return;
            }

            // Calculate a new order
            $relation = $this->$relationName();
            $order = $relation->max($relation->qualifyPivotColumn($column));
            foreach ((array) $attached as $id) {
                $relation->updateExistingPivot($id, [$column => ++$order]);
            }
        });
    }

    /**
     * defineSortableRelations will spin over every relation and check for pivotSortable mode
     */
    protected function defineSortableRelations()
    {
        $interactsWithPivot = ['belongsToMany'];
        $sortableRelations = [];

        foreach ($interactsWithPivot as $type) {
            foreach ($this->$type as $name => $definition) {
                if (!isset($definition['pivotSortable'])) {
                    continue;
                }

                $sortableRelations[$name] = $attrName = $definition['pivotSortable'];

                // Ensure attribute is included in pivot definition
                if (!isset($definition['pivot']) || !in_array($attrName, $definition['pivot'])) {
                    $this->$type[$name]['pivot'][] = $attrName;
                }

                // Apply sort by the pivot table column name
                if (!isset($definition['order']) && isset($definition['table'])) {
                    $this->$type[$name]['order'][] = $definition['table'].'.'.$attrName;
                }
            }
        }

        $this->sortableRelationDefinitions = $sortableRelations;
    }

    /**
     * setSortableRelationOrder sets the sort order of records to the specified orders. If the orders is
     * undefined, the record identifier is used. If reference pool is true, then an incrementing
     * pool is used.
     * @param string $relationName
     * @param mixed $itemIds
     * @param  array|null|bool $referencePool
     */
    public function setSortableRelationOrder($relationName, $itemIds, $referencePool = null)
    {
        if (!$this->isSortableRelation($relationName)) {
            throw new Exception("Invalid setSortableRelationOrder call - the relation '{$relationName}' is not sortable");
        }

        if (!is_array($itemIds)) {
            return;
        }

        $sortKeyMap = $this->processSortableRelationOrdersInternal($relationName, $itemIds, $referencePool);
        if (count($itemIds) !== count($sortKeyMap)) {
            throw new Exception('Invalid setSortableRelationOrder call - count of itemIds do not match count of itemOrders');
        }

        $upsert = [];
        foreach ($itemIds as $id) {
            $sortOrder = $sortKeyMap[$id] ?? null;
            if ($sortOrder !== null) {
                $upsert[] = ['id' => $id, 'sort_order' => (int) $sortOrder];
            }
        }

        if ($upsert) {
            foreach ($upsert as $update) {
                $result = $this->exists ? $this->$relationName()->updateExistingPivot($update['id'], [
                    $this->getRelationSortOrderColumn($relationName) => $update['sort_order']
                ]) : 0;

                if (!$result && $this->sessionKey) {
                    Db::table('deferred_bindings')
                        ->where('master_field', $relationName)
                        ->where('master_type', get_class($this))
                        ->where('session_key', $this->sessionKey)
                        ->where('slave_id', $update['id'])
                        ->limit(1)
                        ->update(['sort_order' => $update['sort_order']]);
                }
            }
        }
    }

    /**
     * processSortableRelationOrdersInternal
     */
    protected function processSortableRelationOrdersInternal($relationName, $itemIds, $referencePool = null): array
    {
        // Build incrementing reference pool
        if ($referencePool === true) {
            $referencePool = range(1, count($itemIds));
        }
        else {
            // Extract a reference pool from the database
            if (!$referencePool) {
                $referencePool = $this->$relationName()
                    ->whereIn($this->getKeyName(), $itemIds)
                    ->pluck($this->getRelationSortOrderColumn($relationName))
                    ->all();
            }

            // Check for corrupt values, if found, reset with a unique pool
            $referencePool = array_unique(array_filter($referencePool, 'strlen'));
            if (count($referencePool) !== count($itemIds)) {
                $referencePool = $itemIds;
            }

            // Sort pool to apply against the sorted items
            sort($referencePool);
        }

        // Process the item orders to a sort key map
        $result = [];
        foreach ($itemIds as $index => $id) {
            $result[$id] = $referencePool[$index];
        }

        return $result;
    }

    /**
     * isSortableRelation returns true if the supplied relation is sortable.
     */
    public function isSortableRelation($relationName)
    {
        return isset($this->sortableRelationDefinitions[$relationName]);
    }

    /**
     * getRelationSortOrderColumn gets the name of the "sort_order" column.
     */
    public function getRelationSortOrderColumn(string $relation): string
    {
        return $this->sortableRelationDefinitions[$relation] ?? 'sort_order';
    }

    /**
     * getSortableRelations returns all configured sortable relations.
     */
    protected function getSortableRelations(): array
    {
        return $this->sortableRelationDefinitions;
    }
}
