<?php namespace October\Rain\Database\Traits;

use Db;
use Exception;
use DateTime;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Revisionable trait tracks changes to specific attributes
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Revisionable
{
    /**
     * @var array revisionable list of attributes to monitor for changes and store revisions for.
     *
     * protected $revisionable = [];
     */

    /**
     * @var int revisionableLimit is the maximum number of revision records to keep.
     *
     * public $revisionableLimit = 500;
     */

    /**
     * @var const REVISION_HISTORY changes the relation name used to store revisions.
     *
     * const REVISION_HISTORY = 'revision_history';
     */

    /**
     * @var bool revisionsEnabled flag for arbitrarily disabling revision history.
     */
    public $revisionsEnabled = true;

    /**
     * initializeRevisionable trait for a model.
     */
    public function initializeRevisionable()
    {
        if (!is_array($this->revisionable)) {
            throw new Exception(sprintf(
                'The $revisionable property in %s must be an array to use the Revisionable trait.',
                static::class
            ));
        }

        $this->bindEvent('model.afterUpdate', function () {
            $this->revisionableAfterUpdate();
        });

        $this->bindEvent('model.afterDelete', function () {
            $this->revisionableAfterDelete();
        });
    }

    /**
     * revisionableAfterUpdate event
     */
    public function revisionableAfterUpdate()
    {
        if (!$this->revisionsEnabled) {
            return;
        }

        $relation = $this->getRevisionHistoryName();
        $relationObject = $this->{$relation}();
        $revisionModel = $relationObject->getRelated();

        $toSave = [];
        $dirty = $this->getDirty();
        foreach ($dirty as $attribute => $value) {
            if (!in_array($attribute, $this->revisionable)) {
                continue;
            }

            $toSave[] = [
                'field' => $attribute,
                'old_value' => array_get($this->original, $attribute),
                'new_value' => $value,
                'revisionable_type' => $relationObject->getMorphClass(),
                'revisionable_id' => $this->getKey(),
                'user_id' => $this->revisionableGetUser(),
                'cast' => $this->revisionableGetCastType($attribute),
                'created_at' => new DateTime,
                'updated_at' => new DateTime
            ];
        }

        // Nothing to do
        if (!count($toSave)) {
            return;
        }

        Db::table($revisionModel->getTable())->insert($toSave);
        $this->revisionableCleanUp();
    }

    /**
     * revisionableAfterDelete event
     */
    public function revisionableAfterDelete()
    {
        if (!$this->revisionsEnabled) {
            return;
        }

        $softDeletes = in_array(
            \October\Rain\Database\Traits\SoftDelete::class,
            class_uses_recursive(static::class)
        );

        if (!$softDeletes) {
            return;
        }

        if (!in_array('deleted_at', $this->revisionable)) {
            return;
        }

        $relation = $this->getRevisionHistoryName();
        $relationObject = $this->{$relation}();
        $revisionModel = $relationObject->getRelated();

        $toSave = [
            'field' => 'deleted_at',
            'old_value' => null,
            'new_value' => $this->deleted_at,
            'revisionable_type' => $relationObject->getMorphClass(),
            'revisionable_id' => $this->getKey(),
            'user_id' => $this->revisionableGetUser(),
            'created_at' => new DateTime,
            'updated_at' => new DateTime
        ];

        Db::table($revisionModel->getTable())->insert($toSave);
        $this->revisionableCleanUp();
    }

    /*
     * revisionableCleanUp deletes revision records exceeding the limit.
     */
    protected function revisionableCleanUp()
    {
        $relation = $this->getRevisionHistoryName();
        $relationObject = $this->{$relation}();
        $revisionLimit = property_exists($this, 'revisionableLimit')
            ? (int) $this->revisionableLimit
            : 500;

        $toDelete = $relationObject
            ->orderBy('id', 'desc')
            ->skip($revisionLimit)
            ->limit(64)
            ->get();

        foreach ($toDelete as $record) {
            $record->delete();
        }
    }

    /**
     * revisionableGetCastType
     */
    protected function revisionableGetCastType($attribute)
    {
        if (in_array($attribute, $this->getDates())) {
            return 'date';
        }

        return null;
    }

    /**
     * revisionableGetUser
     */
    protected function revisionableGetUser()
    {
        if (method_exists($this, 'getRevisionableUser')) {
            $user = $this->getRevisionableUser();

            return $user instanceof EloquentModel
                ? $user->getKey()
                : $user;
        }

        return null;
    }

    /**
     * getRevisionHistoryName
     * @return string
     */
    public function getRevisionHistoryName()
    {
        return defined('static::REVISION_HISTORY') ? static::REVISION_HISTORY : 'revision_history';
    }
}
