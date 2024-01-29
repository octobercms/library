<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany as MorphManyBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use October\Rain\Database\Attach\File as FileModel;

/**
 * AttachMany
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class AttachMany extends MorphManyBase
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
        // Append a single newly uploaded file(s)
        if ($value instanceof UploadedFile) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $this->create(['data' => $value]);
            });
            return;
        }

        // Append existing File model
        if ($value instanceof FileModel) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $this->add($value);
            });
            return;
        }

        // Process multiple values
        $files = $models = $keys = [];
        if (is_array($value)) {
            foreach ($value as $_value) {
                if ($_value instanceof UploadedFile) {
                    $files[] = $_value;
                }
                elseif ($_value instanceof FileModel) {
                    $models[] = $_value;
                }
                elseif (is_numeric($_value)){
                    $keys[] = $_value;
                }
            }
        }

        if ($files) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($files) {
                foreach ($files as $file) {
                    $this->create(['data' => $file]);
                }
            });
        }

        if ($keys) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($keys) {
                $models = $this->getRelated()
                    ->whereIn($this->getRelatedKeyName(), (array) $keys)
                    ->get()
                ;

                foreach ($models as $model) {
                    $this->add($model);
                }
            });
        }

        if ($models) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($models) {
                foreach ($models as $model) {
                    $this->add($model);
                }
            });
        }
    }

    /**
     * getSimpleValue helper for getting this relationship simple value,
     * generally useful with form values.
     * @return array|null
     */
    public function getSimpleValue()
    {
        $value = null;
        $relationName = $this->relationName;

        if ($this->parent->relationLoaded($relationName)) {
            $files = $this->parent->getRelation($relationName);
        }
        else {
            $files = $this->getResults();
        }

        if ($files) {
            $value = [];
            foreach ($files as $file) {
                $value[] = $file->getKey();
            }
        }

        return $value;
    }
}
