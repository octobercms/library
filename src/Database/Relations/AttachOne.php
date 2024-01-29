<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne as MorphOneBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use October\Rain\Database\Attach\File as FileModel;

/**
 * AttachOne
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class AttachOne extends MorphOneBase
{
    use AttachOneOrMany;
    use DefinedConstraints;

    /**
     * __construct a new has many relationship instance.
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $isPublic, $localKey, $relationName = null)
    {
        $this->relationName = $relationName;

        $this->public = $isPublic;

        parent::__construct($query, $parent, $type, $id, $localKey);

        $this->addDefinedConstraints();
    }

    /**
     * setSimpleValue helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
     * @param mixed $value
     * @return void
     */
    public function setSimpleValue($value)
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        // Newly uploaded file
        if ($value instanceof UploadedFile) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $file = $this->create(['data' => $value]);
                $this->parent->setRelation($this->relationName, $file);
            });
        }
        // Existing File model
        elseif ($value instanceof FileModel) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $this->add($value);
            });
        }
        // Model key
        elseif (is_numeric($value)) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                if ($model = $this->getRelated()->find($value)) {
                    $this->add($model);
                }
            });
        }

        // The relation is set here to satisfy validation
        $this->parent->setRelation($this->relationName, $value);
    }

    /**
     * getSimpleValue helper for getting this relationship simple value,
     * generally useful with form values.
     * @return string|null
     */
    public function getSimpleValue()
    {
        $value = null;
        $relationName = $this->relationName;

        if ($this->parent->relationLoaded($relationName)) {
            $file = $this->parent->getRelation($relationName);
        }
        else {
            $file = $this->getResults();
        }

        if ($file) {
            $value = $file->getKey();
        }

        return $value;
    }
}
