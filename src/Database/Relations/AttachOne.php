<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne as MorphOneBase;
use October\Rain\Database\Attach\File as FileModel;

class AttachOne extends MorphOneBase
{
    use AttachOneOrMany;
    use DefinedConstraints;

    /**
     * Create a new has many relationship instance.
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $isPublic, $localKey, $relationName = null)
    {
        $this->relationName = $relationName;

        $this->public = $isPublic;

        parent::__construct($query, $parent, $type, $id, $localKey);

        $this->addDefinedConstraints();
    }

    /**
     * Helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
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
     * Helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        if ($value = $this->getSimpleValueInternal()) {
            return $value->getPath();
        }

        return null;
    }

    /**
     * Helper for getting this relationship validation value.
     */
    public function getValidationValue()
    {
        if ($value = $this->getSimpleValueInternal()) {
            return $this->makeValidationFile($value);
        }

        return null;
    }

    /**
     * Internal method used by `getSimpleValue` and `getValidationValue`
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
