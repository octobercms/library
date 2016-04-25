<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany as MorphManyBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use October\Rain\Database\Attach\File as FileModel;

class AttachMany extends MorphManyBase
{
    use AttachOneOrMany;

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
        /*
         * Newly uploaded file(s)
         */
        if ($value instanceof UploadedFile) {
            $this->parent->bindEventOnce('model.afterSave', function() use ($value) {
                $this->create(['data' => $value]);
            });
        }
        elseif (is_array($value)) {
            $files = [];
            foreach ($value as $_value) {
                if ($_value instanceof UploadedFile) {
                    $files[] = $_value;
                }
            }
            $this->parent->bindEventOnce('model.afterSave', function() use ($files) {
                foreach ($files as $file) {
                    $this->create(['data' => $file]);
                }
            });
        }
        /*
         * Existing File model
         */
        elseif ($value instanceof FileModel) {
            $this->parent->bindEventOnce('model.afterSave', function() use ($value) {
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

        $files = ($sessionKey = $this->parent->sessionKey)
            ? $this->withDeferred($sessionKey)->get()
            : $this->parent->{$this->relationName};

        if ($files) {
            $value = [];
            $files->each(function($file) use (&$value){
                $value[] = $file->getPath();
            });
        }

        return $value;
    }
}