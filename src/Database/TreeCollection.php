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
     * @param bool $removeOrphans Remove nodes that exist without their parents.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function toNested($removeOrphans = true)
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
            if (!$parentKey = $model->getParentId())
                continue;

            if (array_key_exists($parentKey, $collection)) {
                $collection[$parentKey]->children[] = $model;
                $nestedKeys[] = $model->getKey();
            }
            elseif ($removeOrphans) {
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