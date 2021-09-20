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
     * getPrimaryDraftId
     */
    public function getPrimaryDraftId()
    {
        return $this->primary_id;
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
     * setPrimaryDraft
     */
    public function setPrimaryDraft($model)
    {
        $this->primary_id = $model->getKey();
    }

    /**
     * prepareDraftQuery
     */
    protected function prepareDraftQuery()
    {
        if ($this->primary_id) {
            return $this->where('draftable_type', $this->draftable_type)
                ->where('primary_id', $this->primary_id);
        }

        if ($this->draftable->exists) {
            return $this->where('draftable_type', $this->draftable_type)
                ->where('primary_id', $this->draftable->getKey());
        }

        return null;
    }
}
