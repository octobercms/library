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
     * saveVersionSnapshot
     */
    public function saveVersionSnapshot(array $attrs = [])
    {
        $model = $this->replicateVersionModelInternal();

        $model->{$this->getIsVersionColumn()} = true;

        $model->save();

        $version = $model->{$this->getVersionableRecordName()};

        $version->fill($attrs);

        $version->setPrimaryVersion($this);

        $version->save();

        return $model;
    }

    /**
     * restoreVersionSnapshot
     */
    public function restoreVersionSnapshot()
    {
        $version = $this->{$this->getVersionableRecordName()};

        $primaryModel = $version->getPrimaryVersion();

        $primaryModel->saveVersionSnapshot();

        $this->replicateVersionModelInternal($primaryModel);

        $primaryModel->save();
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
    protected function replicateVersionModelInternal($toModel = null)
    {
        $defaults = [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        $attributes = array_except($this->attributes, $defaults);

        $instance = $toModel ?: $this->newInstance();

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
