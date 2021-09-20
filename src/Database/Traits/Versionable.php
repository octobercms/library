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
     * getVersionableTransferAttributes override method
     */
    protected function getVersionableTransferAttributes()
    {
        return [];
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
        $model = $this->newInstance();

        foreach ($this->getVersionableTransferAttributes() as $attr) {
            $model->$attr = $this->$attr;
        }

        $model->{$this->getIsVersionColumn()} = true;

        $model->save(['force' => true]);

        $version = $model->{$this->getVersionableRecordName()};

        $version->fill($attrs);

        $version->setPrimaryVersion($this);

        $version->setVersionBuildNumber();

        $version->save();

        return $model;
    }

    /**
     * restoreVersionSnapshot
     */
    public function restoreVersionSnapshot($toModel)
    {
        $toModel->saveVersionSnapshot();

        foreach ($this->getVersionableTransferAttributes() as $attr) {
            $toModel->$attr = $this->$attr;
        }

        $toModel->save(['force' => true]);
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
