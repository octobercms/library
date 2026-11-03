<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * TranslatableAttachOneOrMany stores attachments per locale and reads the default locale files when a locale has none.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait TranslatableAttachOneOrMany
{
    /**
     * @var string|null attachmentLocale suffixes the field column, null for the default locale.
     */
    protected $attachmentLocale;

    /**
     * @var bool useFallback reads the default locale files when the locale has none of its own.
     */
    protected $useFallback = true;

    /**
     * __construct resolves the locale from the parent model before the constraints are added.
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $isPublic, $localKey, $relationName = null)
    {
        $this->attachmentLocale = $this->resolveAttachmentLocale($parent);

        parent::__construct($query, $parent, $type, $id, $isPublic, $localKey, $relationName);
    }

    /**
     * resolveAttachmentLocale returns the active locale when it differs from the default locale.
     */
    protected function resolveAttachmentLocale(Model $parent): ?string
    {
        if (!method_exists($parent, 'shouldTranslate') || !$parent->shouldTranslate()) {
            return null;
        }

        return $parent->getLocale();
    }

    /**
     * getAttachmentField returns the relation name with a locale suffix for non-default locales.
     */
    public function getAttachmentField(): string
    {
        if ($this->attachmentLocale === null) {
            return $this->relationName;
        }

        return $this->relationName . ':' . $this->attachmentLocale;
    }

    /**
     * getAttachmentLocale returns the locale this relation stores files under, or null for the default locale.
     */
    public function getAttachmentLocale(): ?string
    {
        return $this->attachmentLocale;
    }

    /**
     * withoutFallback reads only the files stored under the active locale.
     */
    public function withoutFallback()
    {
        $this->useFallback = false;

        return $this;
    }

    /**
     * usesFallback returns true when reads resolve to the default locale files for a locale without its own.
     */
    public function usesFallback(): bool
    {
        return $this->useFallback && $this->attachmentLocale !== null;
    }

    /**
     * newAttachmentQuery returns a query for the parent's files of this relation in every locale.
     */
    public function newAttachmentQuery()
    {
        return $this->related->newQuery()
            ->where($this->morphType, $this->morphClass)
            ->where($this->foreignKey, '=', $this->getParentKey())
            ->whereNotNull($this->foreignKey)
            ->where(function ($query) {
                $query
                    ->where('field', $this->relationName)
                    ->orWhere('field', 'like', $this->relationName . ':%');
            });
    }

    /**
     * getResults reads the locale files, or the default locale files when the locale has none.
     */
    public function getResults()
    {
        if (!$this->usesFallback() || $this->getParentKey() === null) {
            return parent::getResults();
        }

        $query = $this->related->newQuery()
            ->where($this->morphType, $this->morphClass)
            ->where($this->foreignKey, '=', $this->getParentKey());

        $this->addResolvedFieldConstraint($query);

        $this->addDefinedConstraintsToQuery($query);

        if ($this instanceof AttachOne) {
            return $query->first() ?: $this->getDefaultFor($this->parent);
        }

        return $query->get();
    }

    /**
     * addEagerConstraints resolves the fallback per parent model for an eager load.
     */
    public function addEagerConstraints(array $models)
    {
        if (!$this->usesFallback()) {
            parent::addEagerConstraints($models);
            return;
        }

        $this->addCommonEagerConstraints($models);

        $this->addResolvedFieldConstraint($this->query);
    }

    /**
     * getRelationExistenceQuery resolves the fallback for has and count queries.
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if (!$this->usesFallback()) {
            return parent::getRelationExistenceQuery($query, $parentQuery, $columns);
        }

        $query = $query
            ->select($columns)
            ->whereColumn($this->getExistenceCompareKey(), '=', $this->getQualifiedParentKeyName())
            ->where($this->morphType, $this->morphClass);

        $this->addResolvedFieldConstraint($query);

        return $query;
    }

    /**
     * addResolvedFieldConstraint matches the locale field, or the default field when the parent has no locale files.
     */
    protected function addResolvedFieldConstraint($query)
    {
        $table = $this->related->getTable();
        $localeField = $this->getAttachmentField();

        $query->where(function ($query) use ($table, $localeField) {
            $query->where($table . '.field', $localeField)->orWhere(function ($query) use ($table, $localeField) {
                $query->where($table . '.field', $this->relationName)->whereNotExists(function ($query) use ($table, $localeField) {
                    $query
                        ->selectRaw(1)
                        ->from($table . ' as locale_files')
                        ->whereColumn('locale_files.' . $this->getMorphType(), $this->morphType)
                        ->whereColumn('locale_files.' . $this->getForeignKeyName(), $this->foreignKey)
                        ->where('locale_files.field', $localeField);
                });
            });
        });
    }

    /**
     * remove clears the loaded relation so the next read can fall back to the default locale files.
     */
    public function remove(Model $model, $sessionKey = null)
    {
        parent::remove($model, $sessionKey);

        if ($sessionKey === null && $this->usesFallback()) {
            $this->parent->unsetRelation($this->relationName);
        }
    }

    /**
     * setSimpleValue clears the loaded relation when emptied so reads fall back to the default locale files.
     */
    public function setSimpleValue($value)
    {
        parent::setSimpleValue($value);

        $isEmpty = $this instanceof AttachOne && is_array($value) ? !current($value) : !$value;
        if (!$isEmpty || !$this->usesFallback()) {
            return;
        }

        $this->parent->unsetRelation($this->relationName);

        $this->parent->bindEventOnce('model.afterSave', function () {
            $this->parent->unsetRelation($this->relationName);
        });
    }
}
