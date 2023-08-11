<?php namespace October\Rain\Database\Traits;

/**
 * SluggableTree trait creates structured slugs, called full slugs. Calculating full slugs
 * must be performed externally since it involves expensive lookups. The model is assumed
 * to have two relations defined: parent, children.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait SluggableTree
{
    /**
     * fullSlugAttributes calculates full slugs for this model and all other related ones
     * @return void
     */
    public function fullSlugAttributes()
    {
        $this->setFullSluggedValue($this);
    }

    /**
     * setFullSluggedValue will set the fullslug value on a model
     */
    protected function setFullSluggedValue($model)
    {
        $fullslugAttr = $this->getFullSluggableFullSlugColumnName();
        $proposedSlug = $this->getFullSluggableAttributeValue($model);

        if ($model->{$fullslugAttr} != $proposedSlug) {
            $model
                ->newQuery()
                ->where($model->getKeyName(), $model->getKey())
                ->update([$fullslugAttr => $proposedSlug]);
        }

        if ($children = $model->children) {
            foreach ($children as $child) {
                $this->setFullSluggedValue($child);
            }
        }
    }

    /**
     * getFullSluggableAttributeValue
     */
    protected function getFullSluggableAttributeValue($model, $fullslug = '')
    {
        $slugAttr = $this->getFullSluggableSlugColumnName();
        $fullslug = $model->{$slugAttr} . '/' . $fullslug;

        if ($parent = $model->parent()->withoutGlobalScopes()->first()) {
            $fullslug = $this->getFullSluggableAttributeValue($parent, $fullslug);
        }

        return rtrim($fullslug, '/');
    }

    /**
     * getFullSluggableFullSlugColumnName gets the name of the "fullslug" column.
     * @return string
     */
    public function getFullSluggableFullSlugColumnName()
    {
        return defined('static::FULLSLUG') ? static::FULLSLUG : 'fullslug';
    }

    /**
     * getFullSluggableSlugColumnName gets the name of the "slug" column.
     * @return string
     */
    public function getFullSluggableSlugColumnName()
    {
        return defined('static::SLUG') ? static::SLUG : 'slug';
    }
}
