<?php namespace October\Rain\Database\Scopes;

use Site;
use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Scope as ScopeInterface;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

/**
 * MultisiteScope
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class MultisiteScope implements ScopeInterface
{
    /**
     * @var array extensions to be added to the builder.
     */
    protected $extensions = ['WithSite', 'WithSites', 'WithSyncSites'];

    /**
     * apply the scope to a given Eloquent query builder.
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        if ($model->isMultisiteEnabled() && !Site::hasGlobalContext()) {
            $builder->where($model->getQualifiedSiteIdColumn(), Site::getSiteIdFromContext());
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
     * addWithSite removes this scope and includes the specified site
     */
    protected function addWithSite(BuilderBase $builder)
    {
        $builder->macro('withSite', function (BuilderBase $builder, $siteId) {
            return $builder
                ->withoutGlobalScope($this)
                ->where($builder->getModel()->getQualifiedSiteIdColumn(), $siteId)
            ;
        });
    }

    /**
     * addWithSites removes this scope and includes everything.
     */
    protected function addWithSites(BuilderBase $builder)
    {
        $builder->macro('withSites', function (BuilderBase $builder, $siteIds = null) {
            if (!is_array($siteIds)) {
                return $builder->withoutGlobalScope($this);
            }

            return $builder
                ->withoutGlobalScope($this)
                ->whereIn($builder->getModel()->getQualifiedSiteIdColumn(), $siteIds)
            ;
        });
    }

    /**
     * addWithSyncSites removes this scope and includes sites that should be synced with this model
     */
    protected function addWithSyncSites(BuilderBase $builder)
    {
        $builder->macro('withSyncSites', function (BuilderBase $builder) {
            return $builder->withSites($builder->getModel()->getMultisiteSyncSites());
        });
    }
}
