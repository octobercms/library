<?php namespace October\Rain\Database\Traits;

use October\Rain\Database\Scopes\VersionableScope;

/**
 * Versionable trait allows version versions of models
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Versionable
{
    /**
     * @var string|null versionableSaveMode for saving the version.
     */
    protected $versionableSaveMode;

    /**
     * @var array versionableSaveAttrs contains version notes
     */
    protected $versionableSaveAttrs = [];

    /**
     * bootVersionable trait for a model.
     */
    public static function bootVersionable()
    {
        static::addGlobalScope(new VersionableScope);

        static::extend(function ($model) {
            $model->bindEvent('model.beforeDelete', function() use ($model) {
                $model->versionableDeleteInternal();
            });
        });
    }

    /**
     * countVersions will return the number of available versions.
     */
    public function countVersions(): int
    {
        return $this->{$this->getVersionableRecordName()}->countVersions();
    }

    /**
     * createNewVersion
     */
    public function createNewVersion(array $attrs = [])
    {
        $model = $this->replicateVersionModelInternal();

        $model->{$this->getIsVersionColumn()} = true;

        $model->save(['force' => true]);

        $version = $model->{$this->getVersionableRecordName()};

        $version->fill($attrs);

        $version->setVersionParent($this);

        $version->save();

        return $model;
    }

    /**
     * isVersionStatus
     */
    public function isVersionStatus(): bool
    {
        return (bool) $this->{$this->getIsVersionColumn()};
    }

    /**
     * versionableDeleteInternal
     */
    public function versionableDeleteInternal(): void
    {
        $version = $this->{$this->getVersionableRecordName()};

        if ($version->exists) {
            $version->delete();
        }
    }

    /**
     * replicateVersionModelInternal will transfer relationship values on to the supplied
     * model using the simple setter/getter interface.
     */
    protected function replicateVersionModelInternal()
    {
        $defaults = [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        $attributes = array_except($this->attributes, $defaults);

        $instance = $this->newInstance();

        $instance->setRawAttributes($attributes);

        foreach ($this->getRelationDefinitions() as $type => $definitions) {
            foreach ($definitions as $attr => $definition) {
                $instance->$attr = $this->$attr;
            }
        }

        return $instance;
    }

    /**
     * getVersionableNoteName
     */
    public function getVersionableRecordName(): string
    {
        return 'version_record';
    }

    /**
     * getIsVersionColumn gets the name of the "is_version" column.
     */
    public function getIsVersionColumn(): string
    {
        return 'is_version';
    }

    /**
     * getQualifiedIsVersionColumn gets the fully qualified "is_version" column.
     */
    public function getQualifiedIsVersionColumn(): string
    {
        return $this->getTable().'.'.$this->getIsVersionColumn();
    }
}
