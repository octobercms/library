<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne as MorphOneBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use October\Rain\Database\Attach\File as FileModel;

class AttachOne extends MorphOneBase
{
    use AttachOneOrMany;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * @var boolean Default value for file public or protected state
     */
    protected $public;

    /**
     * Create a new has many relationship instance.
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $isPublic, $localKey, $relationName = null)
    {
        $this->relationName = $relationName;
        $this->public = $isPublic;

        parent::__construct($query, $parent, $type, $id, $localKey);
    }

    /**
     * Helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
     */
    public function setSimpleValue($value)
    {
        if (is_array($value))
            $value = reset($value);

        /*
         * Newly uploaded file
         */
        if ($value instanceof UploadedFile) {
            $this->parent->bindEventOnce('model.afterSave', function() use ($value){
                $this->create(['data' => $value]);
            });
        }
        /*
         * Existing File model
         */
        elseif ($value instanceof FileModel) {
            $this->parent->bindEventOnce('model.afterSave', function() use ($value){
                $this->add($value);
            });
        }
    }

    /**
     * Helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        $value = null;

        $file = ($sessionKey = $this->parent->sessionKey)
            ? $this->withDeferred($sessionKey)->first()
            : $this->parent->{$this->relationName};

        if ($file) {
            $value = $file->getPath();
        }

        return $value;
    }
}