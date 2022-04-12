<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne as MorphOneBase;
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

        /*
         * Newly uploaded file
         */
        if ($this->isValidFileData($value)) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $file = $this->create(['data' => $value]);
                $this->parent->setRelation($this->relationName, $file);
            });
        }
        /*
         * Existing File model
         */
        elseif ($value instanceof FileModel) {
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $this->add($value);
            });
        }

        /*
         * The relation is set here to satisfy `getValidationValue`
         */
        $this->parent->setRelation($this->relationName, $value);
    }

    /**
     * getSimpleValue helper for getting this relationship simple value,
     * generally useful with form values.
     * @return string|null
     */
    public function getSimpleValue()
    {
        if ($value = $this->getSimpleValueInternal()) {
            return $value->getPath();
        }

        return null;
    }

    /**
     * getValidationValue helper for getting this relationship validation value.
     * @return mixed
     */
    public function getValidationValue()
    {
        if ($value = $this->getSimpleValueInternal()) {
            return $this->makeValidationFile($value);
        }

        return null;
    }

    /**
     * getSimpleValueInternal method used by `getSimpleValue` and `getValidationValue`.
     * @return Model|null
     */
    protected function getSimpleValueInternal()
    {
        $value = null;

        $file = ($sessionKey = $this->parent->sessionKey)
            ? $this->withDeferred($sessionKey)->first()
            : $this->parent->{$this->relationName};

        if ($file) {
            $value = $file;
        }

        return $value;
    }
}
