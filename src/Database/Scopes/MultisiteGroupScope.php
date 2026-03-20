<?php namespace October\Rain\Database\Scopes;

use Site;
use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Scope as ScopeInterface;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

/**
 * MultisiteGroupScope applies site group scoping to models that use
 * the MultisiteGroup trait. Unlike MultisiteScope which filters by
 * individual site_id, this scope filters by site_group_id to scope
 * records to a tenant.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class MultisiteGroupScope implements ScopeInterface
{
    /**
     * @var array extensions to be added to the builder.
     */
    protected $extensions = ['WithSiteGroup', 'WithSiteGroups'];

    /**
     * apply the scope to a given Eloquent query builder.
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        if ($model->isMultisiteGroupEnabled() && !Site::hasGlobalContext()) {
            $builder->where(
                $model->getQualifiedSiteGroupIdColumn(),
                Site::getSiteGroupIdFromContext()
            );
        }
    }

    /**
     * extend the Eloquent query builder.
     */
    public function extend(BuilderBase $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * addWithSiteGroup removes this scope and includes the specified site group
     */
    protected function addWithSiteGroup(BuilderBase $builder)
    {
        $builder->macro('withSiteGroup', function (BuilderBase $builder, $groupId) {
            return $builder
                ->withoutGlobalScope($this)
                ->where($builder->getModel()->getQualifiedSiteGroupIdColumn(), $groupId)
            ;
        });
    }

    /**
     * addWithSiteGroups removes this scope and includes everything,
     * or filters by an array of group ids.
     */
    protected function addWithSiteGroups(BuilderBase $builder)
    {
        $builder->macro('withSiteGroups', function (BuilderBase $builder, $groupIds = null) {
            if (!is_array($groupIds)) {
                return $builder->withoutGlobalScope($this);
            }

            return $builder
                ->withoutGlobalScope($this)
                ->whereIn($builder->getModel()->getQualifiedSiteGroupIdColumn(), $groupIds)
            ;
        });
    }
}
