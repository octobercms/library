<?php namespace October\Rain\Database;

use Illuminate\Database\Eloquent\Collection as CollectionBase;

/**
 * Custom collection used by NestedTree trait.
 *
 * General access methods:
 *
 *   $collection->toNested(); // Converts collection to an eager loaded one.
 *
 */
class TreeCollection extends CollectionBase
{

    /**
     * Converts a flat collection of nested set models to an set where
     * children is eager loaded
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function toNested()
    {
        /*
         * Set new collection for "children" relations
         */
        $collection = $this->getDictionary();
        foreach ($collection as $key => $model) {
            $model->setRelation('children', new CollectionBase);
        }

        /*
         * Assign all child nodes to their parents
         */
        $nestedKeys = [];
        foreach($collection as $key => $model) {
            $parentKey = $model->getParentId();

            if ($parentKey !== null && array_key_exists($parentKey, $collection)) {
                $collection[$parentKey]->children[] = $model;
                $nestedKeys[] = $model->getKey();
            }
        }

        /*
         * Remove processed nodes
         */
        foreach ($nestedKeys as $key) {
            unset($collection[$key]);
        }

        return new CollectionBase($collection);
    }

}