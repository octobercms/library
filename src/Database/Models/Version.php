<?php namespace October\Rain\Database\Models;

use Model;

/**
 * Version record
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Version extends Model
{
    /**
     * @var string table associated with the model
     */
    protected $table = 'versions';

    /**
     * @var array fillable attributes that are mass assignable
     */
    protected $fillable = [
        'notes',
    ];

    /**
     * @var array morphTo relation
     */
    public $morphTo = [
        'versionable' => ['default' => true]
    ];

    /**
     * getVersionId
     */
    public function getVersionId()
    {
        return $this->versionable_id;
    }

    /**
     * getPrimaryVersionId
     */
    public function getPrimaryVersionId()
    {
        return $this->primary_id;
    }

    /**
     * getVersions
     */
    public function getVersions()
    {
        if ($query = $this->prepareVersionQuery()) {
            return $query->get();
        }

        return [];
    }

    /**
     * countVersions
     */
    public function countVersions(): int
    {
        if ($query = $this->prepareVersionQuery()) {
            return $query->count();
        }

        return 0;
    }

    /**
     * setVersionBuildNumber
     */
    public function setVersionBuildNumber()
    {
        $build = 0;

        if ($query = $this->prepareVersionQuery()) {
            $build = $query->orderBy('build', 'desc')->value('build') ?: 0;
        }

        $this->build = $build + 1;
    }

    /**
     * setPrimaryVersion
     */
    public function setPrimaryVersion($model)
    {
        $this->primary_id = $model->getKey();
    }

    /**
     * prepareVersionQuery
     */
    protected function prepareVersionQuery()
    {
        if ($this->primary_id) {
            return $this->where('versionable_type', $this->versionable_type)
                ->where('primary_id', $this->primary_id);
        }

        if ($this->versionable->exists) {
            return $this->where('versionable_type', $this->versionable_type)
                ->where('primary_id', $this->versionable->getKey());
        }

        return null;
    }
}
