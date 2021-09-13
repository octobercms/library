<?php namespace October\Rain\Database\Models;

use Model;

/**
 * Draft record
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Draft extends Model
{
    /**
     * @var string table associated with the model
     */
    protected $table = 'drafts';

    /**
     * @var array fillable attributes that are mass assignable
     */
    protected $fillable = [
        'name',
        'notes',
    ];

    /**
     * @var array morphTo relation
     */
    public $morphTo = [
        'draftable' => ['default' => true]
    ];

    /**
     * getDraftId
     */
    public function getDraftId()
    {
        return $this->draftable_id;
    }

    /**
     * getDrafts
     */
    public function getDrafts()
    {
        if ($query = $this->prepareDraftQuery()) {
            return $query->get();
        }

        return [];
    }

    /**
     * countDrafts
     */
    public function countDrafts(): int
    {
        if ($query = $this->prepareDraftQuery()) {
            return $query->count();
        }

        return 0;
    }

    /**
     * setDraftParent
     */
    public function setDraftParent($model)
    {
        $this->primary_id = $model->getKey();
    }

    /**
     * prepareDraftQuery
     */
    protected function prepareDraftQuery()
    {
        if (!$this->draftable->exists) {
            return null;
        }

        return $this->where('draftable_type', $this->draftable_type)
            ->where('primary_id', $this->draftable->getKey())
        ;
    }
}
