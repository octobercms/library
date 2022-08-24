<?php namespace October\Rain\Database\Scopes;

use Site;
use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Scope as ScopeInterface;
use Illuminate\Database\Eloquent\Builder as BuilderBase;
use Closure;

/**
 * MultisiteScope
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class MultisiteScope implements ScopeInterface
{
    /**
     * @var bool constraints of this scope applied
     */
    protected static $constraints = true;

    /**
     * @var array extensions to be added to the builder.
     */
    protected $extensions = ['WithSites'];

    /**
     * apply the scope to a given Eloquent query builder.
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        if ($model->isMultisiteEnabled() && static::$constraints) {
            $builder->where($model->getQualifiedSiteIdColumn(), Site::getSiteIdFromContext());
        }
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

    /**
     * hasConstraints returns true if site constraints are currently applied
     * @return bool
     */
    public static function hasConstraints()
    {
        return static::$constraints;
    }

    /**
     * noConstraints runs a callback with this scope constraint disabled.
     * @return mixed
     */
    public static function noConstraints(Closure $callback)
    {
        $previous = static::$constraints;

        static::$constraints = false;

        try {
            return $callback();
        }
        finally {
            static::$constraints = $previous;
        }
    }
}
