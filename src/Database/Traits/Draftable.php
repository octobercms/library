<?php namespace October\Rain\Database\Traits;

use October\Rain\Database\Scopes\DraftableScope;

/**
 * Draftable trait allows draft versions of models
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Draftable
{
    /**
     * @var string|null draftableSaveMode for saving the draft.
     */
    protected $draftableSaveMode;

    /**
     * @var array draftableSaveAttrs contains draft notes.
     */
    protected $draftableSaveAttrs = [];

    /**
     * bootDraftable trait for a model.
     */
    public static function bootDraftable()
    {
        static::addGlobalScope(new DraftableScope);

        static::extend(function ($model) {
            $model->bindEvent('model.saveInternal', function() use ($model) {
                $model->draftableSaveModeInternal();
            });

            $model->bindEvent('model.beforeDelete', function() use ($model) {
                $model->draftableDeleteInternal();
            });
        });
    }

    /**
     * countDrafts will return the number of available drafts.
     */
    public function countDrafts(): int
    {
        return $this->{$this->getDraftableRecordName()}->countDrafts();
    }

    /**
     * saveAsFirstDraft
     */
    public function saveAsFirstDraft(array $attrs = [])
    {
        $this->{$this->getDraftModeColumn()} = DraftableScope::MODE_NEW_UNSAVED;

        $this->save(['force' => true]);

        $this->reloadRelations($this->getDraftableRecordName());

        $draft = $this->{$this->getDraftableRecordName()};

        $draft->fill($attrs);

        $draft->save();
    }

    /**
     * createNewDraft
     */
    public function createNewDraft(array $attrs = [])
    {
        $model = $this->newInstance();

        $model->{$this->getDraftModeColumn()} = DraftableScope::MODE_DRAFT;

        $model->save(['force' => true]);

        $model->reloadRelations($this->getDraftableRecordName());

        $draft = $model->{$this->getDraftableRecordName()};

        $draft->fill($attrs);

        $draft->setPrimaryDraft($this);

        $draft->save();

        return $model;
    }

    /**
     * setDraftAutosave
     */
    public function setDraftAutosave(array $attrs): void
    {
        $this->draftableSaveMode = null;
        $this->draftableSaveAttrs = $attrs;
    }

    /**
     * setDraftCommit
     */
    public function setDraftCommit(array $attrs): void
    {
        $this->draftableSaveMode = DraftableScope::MODE_NEW_SAVED;
        $this->draftableSaveAttrs = $attrs;
    }

    /**
     * setDraftPublish
     */
    public function setDraftPublish(): void
    {
        $this->draftableSaveMode = DraftableScope::MODE_PUBLISHED;
        $this->draftableSaveAttrs = [];
    }

    /**
     * isDraftStatus
     */
    public function isDraftStatus(): bool
    {
        return $this->{$this->getDraftModeColumn()} !== DraftableScope::MODE_PUBLISHED;
    }

    /**
     * draftableSaveModeInternal
     */
    public function draftableSaveModeInternal(): void
    {
        $draft = $this->{$this->getDraftableRecordName()};

        if ($this->draftableSaveAttrs) {
            $draft->fill($this->draftableSaveAttrs);
            $draft->save();
        }

        if ($this->draftableSaveMode === DraftableScope::MODE_PUBLISHED && $draft->exists) {
            $draft->delete();
        }

        if ($this->draftableSaveMode) {
            $this->{$this->getDraftModeColumn()} = $this->draftableSaveMode;
        }
    }

    /**
     * draftableDeleteInternal
     */
    public function draftableDeleteInternal(): void
    {
        $draft = $this->{$this->getDraftableRecordName()};

        if ($draft->exists) {
            $draft->delete();
        }
    }

    /**
     * getDraftableNoteName
     */
    public function getDraftableRecordName(): string
    {
        return 'draft_record';
    }

    /**
     * getDraftModeColumn gets the name of the "draft_mode" column.
     */
    public function getDraftModeColumn(): string
    {
        return 'draft_mode';
    }

    /**
     * getQualifiedDraftModeColumn gets the fully qualified "draft_mode" column.
     */
    public function getQualifiedDraftModeColumn(): string
    {
        return $this->getTable().'.'.$this->getDraftModeColumn();
    }
}
