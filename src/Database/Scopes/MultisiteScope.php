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
    protected $extensions = ['WithSite', 'WithSites'];

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
     * addWithSite
     */
    protected function addWithSite(BuilderBase $builder)
    {
        $builder->macro('withSite', function (BuilderBase $builder, $siteId) {
            return $builder->where($builder->getModel()->getQualifiedSiteIdColumn(), $siteId);
        });
    }

    /**
     * addWithSites removes this scope and includes everything.
     */
    protected function addWithSites(BuilderBase $builder)
    {
        $builder->macro('withSites', function (BuilderBase $builder) {
            return $builder->withoutGlobalScope($this);
        });
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
}
