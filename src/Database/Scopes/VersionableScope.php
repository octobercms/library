<?php namespace October\Rain\Database\Scopes;

use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Scope as ScopeInterface;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

/**
 * VersionableScope
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class VersionableScope implements ScopeInterface
{
    /**
     * @var array extensions to be added to the builder.
     */
    protected $extensions = ['WithVersions', 'WithOnlyVersions'];

    /**
     * apply the scope to a given Eloquent query builder.
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        $builder->where($model->getQualifiedIsVersionColumn(), false);
    }

    /**
     * extend the query builder with the needed functions.
     */
    public function extend(BuilderBase $builder)
    {
        foreach ($this->extensions as $extension) {
            $this->{"add{$extension}"}($builder);
        }
    }

    /**
     * addWithVersions removes this scope and includes everything.
     */
    protected function addWithVersions(BuilderBase $builder)
    {
        $builder->macro('withVersions', function (BuilderBase $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * addWithOnlyVersions includes only drafts in the query.
     */
    protected function addWithOnlyVersions(BuilderBase $builder)
    {
        $builder->macro('withOnlyVersions', function (BuilderBase $builder) {
            $model = $builder->getModel();

            $builder->withoutGlobalScope($this)
                ->where($model->getQualifiedIsVersionColumn(), true)
            ;

            return $builder;
        });
    }
}
