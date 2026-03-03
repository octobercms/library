<?php namespace October\Rain\Database\Traits;

use App;
use Db;
use Site;
use Exception;

/**
 * Translatable trait provides per-row model translation using a single
 * translation table. Locale is resolved via Site/SiteManager.
 *
 * Usage:
 *
 *     use \October\Rain\Database\Traits\Translatable;
 *
 *     public $translatable = ['name', 'description'];
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Translatable
{
    /**
     * @var string translatableContext is the active locale override
     */
    protected $translatableContext;

    /**
     * @var string translatableDefault is the default locale cache
     */
    protected $translatableDefault;

    /**
     * @var bool translatableUseFallback determines if empty translations fall back to default
     */
    protected $translatableUseFallback = true;

    /**
     * @var array translatableAttributes stores loaded translation data keyed by locale
     */
    protected $translatableAttributes = [];

    /**
     * @var array translatableOriginals stores original translation data for dirty checking
     */
    protected $translatableOriginals = [];

    /**
     * initializeTranslatable trait for a model
     */
    public function initializeTranslatable()
    {
        if (!is_array($this->translatable)) {
            throw new Exception(sprintf(
                'The $translatable property in %s must be an array to use the Translatable trait.',
                static::class
            ));
        }

        $this->morphMany['translations'] = [
            $this->getTranslateAttributeModelClass(),
            'name' => 'model',
            'delete' => true
        ];

        $this->bindEvent('model.saveInternal', function() {
            $this->syncTranslatableAttributes();
        });
    }

    //
    // Locale resolution
    //

    /**
     * getTranslatableContext returns the active locale, resolved lazily from Site.
     * Lazy resolution avoids issues during migrations/seeds when Site isn't booted.
     */
    public function getTranslatableContext()
    {
        if ($this->translatableContext === null) {
            $this->translatableContext = $this->resolveTranslatableLocale();
        }

        return $this->translatableContext;
    }

    /**
     * getTranslatableDefault returns the default locale, resolved lazily from Site.
     */
    public function getTranslatableDefault()
    {
        if ($this->translatableDefault === null) {
            $this->translatableDefault = $this->resolveTranslatableDefaultLocale();
        }

        return $this->translatableDefault;
    }

    /**
     * resolveTranslatableLocale reads the current locale from the Site facade.
     * Override this to change how the active locale is determined.
     */
    protected function resolveTranslatableLocale()
    {
        $site = Site::getSiteFromContext();

        return $site ? $site->hard_locale : $this->getTranslatableDefault();
    }

    /**
     * resolveTranslatableDefaultLocale reads the default locale from the Site facade.
     * Override this to change how the default locale is determined.
     */
    protected function resolveTranslatableDefaultLocale()
    {
        $site = Site::getPrimarySite();

        return $site ? $site->hard_locale : 'en';
    }

    //
    // Activation & bypass
    //

    /**
     * isTranslatableEnabled returns true to indicate the trait is active
     */
    public function isTranslatableEnabled()
    {
        return true;
    }

    /**
     * shouldTranslate returns true when the active locale differs from the default.
     * This is the hot-path guard — called on every getAttribute/setAttribute.
     * Returns false for single-locale installs so the trait is invisible.
     */
    public function shouldTranslate()
    {
        if (!$this->isTranslatableEnabled()) {
            return false;
        }

        return $this->getTranslatableContext() !== $this->getTranslatableDefault();
    }

    /**
     * isTranslatableAttribute checks if a specific attribute should be translated right now.
     * Returns false when: default locale active, or attribute not in $translatable.
     */
    public function isTranslatableAttribute($key)
    {
        if ($key === 'translatable' || !$this->shouldTranslate()) {
            return false;
        }

        return in_array($key, $this->getTranslatableAttributes());
    }

    /**
     * getTranslatableAttributes returns the translatable attribute names
     */
    public function getTranslatableAttributes()
    {
        return $this->translatable;
    }

    //
    // Attribute interception
    //

    /**
     * getAttribute overrides the parent to return translated values when active
     */
    public function getAttribute($key)
    {
        if ($this->isTranslatableAttribute($key)) {
            return $this->getTranslation($key, $this->getTranslatableContext());
        }

        return parent::getAttribute($key);
    }

    /**
     * setAttribute overrides the parent to store translated values when active
     */
    public function setAttribute($key, $value)
    {
        if ($this->isTranslatableAttribute($key)) {
            return $this->setTranslation($key, $this->getTranslatableContext(), $value);
        }

        return parent::setAttribute($key, $value);
    }

    //
    // Reading translations
    //

    /**
     * getTranslation returns the translated value for an attribute and locale
     */
    public function getTranslation($key, $locale, $useFallback = true)
    {
        // Default locale reads from model attributes
        if ($locale === $this->getTranslatableDefault()) {
            $result = $this->attributes[$key] ?? null;
        }
        else {
            if (!array_key_exists($locale, $this->translatableAttributes)) {
                $this->loadTranslatableData($locale);
            }

            if ($this->hasTranslation($key, $locale)) {
                $result = $this->translatableAttributes[$locale][$key] ?? null;
            }
            elseif ($useFallback) {
                $result = $this->attributes[$key] ?? null;
            }
            else {
                $result = null;
            }
        }

        // Handle jsonable attributes
        if (
            is_string($result) &&
            method_exists($this, 'isJsonable') &&
            $this->isJsonable($key)
        ) {
            $result = json_decode($result, true);
        }

        return $result;
    }

    /**
     * getTranslations returns all locale values for a single attribute
     */
    public function getTranslations($key)
    {
        $translations = [];

        // Default locale from model attributes
        $defaultLocale = $this->getTranslatableDefault();
        $defaultValue = $this->attributes[$key] ?? null;
        if ($defaultValue !== null) {
            $translations[$defaultLocale] = $defaultValue;
        }

        // Other locales from translation table
        $rows = $this->translations->where('attribute', $key);
        foreach ($rows as $row) {
            $translations[$row->locale] = $row->value;
        }

        // Handle jsonable attributes
        if (method_exists($this, 'isJsonable') && $this->isJsonable($key)) {
            foreach ($translations as $locale => $value) {
                if (is_string($value)) {
                    $translations[$locale] = json_decode($value, true);
                }
            }
        }

        return $translations;
    }

    /**
     * hasTranslation checks if a translation row exists for one attribute
     */
    public function hasTranslation($key, $locale = null)
    {
        if ($locale === null) {
            $locale = $this->getTranslatableContext();
        }

        // Default locale always has the value in model attributes
        if ($locale === $this->getTranslatableDefault()) {
            $value = $this->attributes[$key] ?? null;
            return $value !== null && $value !== '';
        }

        if (!array_key_exists($locale, $this->translatableAttributes)) {
            $this->loadTranslatableData($locale);
        }

        $value = $this->translatableAttributes[$locale][$key] ?? null;

        return $value !== null && $value !== '';
    }

    /**
     * hasTranslations checks if any translation rows exist for the locale (record-level)
     */
    public function hasTranslations($locale = null)
    {
        if ($locale === null) {
            $locale = $this->getTranslatableContext();
        }

        if ($this->relationLoaded('translations')) {
            return $this->translations->where('locale', $locale)->isNotEmpty();
        }

        return Db::table($this->getTranslateAttributeTable())
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey())
            ->where('locale', $locale)
            ->exists();
    }

    /**
     * getTranslatedLocales returns locales with translations. When a key is provided,
     * returns locales for that specific attribute. Without a key, returns all locales
     * that have any translation rows (record-level).
     */
    public function getTranslatedLocales($key = null)
    {
        if ($this->relationLoaded('translations')) {
            $query = $this->translations;

            if ($key !== null) {
                $query = $query->where('attribute', $key);
            }

            return $query->pluck('locale')->unique()->values()->toArray();
        }

        $query = Db::table($this->getTranslateAttributeTable())
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey());

        if ($key !== null) {
            $query->where('attribute', $key);
        }

        return $query->pluck('locale')->unique()->toArray();
    }

    //
    // Writing translations
    //

    /**
     * setTranslation sets a translated value for an attribute and locale
     */
    public function setTranslation($key, $locale, $value)
    {
        if ($locale === $this->getTranslatableDefault()) {
            $this->attributes[$key] = $value;
            return $value;
        }

        // For new records ensure the base attributes are populated
        if (!$this->exists && !array_key_exists($key, $this->attributes)) {
            $this->attributes[$key] = $value;
        }

        if (!array_key_exists($locale, $this->translatableAttributes)) {
            $this->loadTranslatableData($locale);
        }

        $this->translatableAttributes[$locale][$key] = $value;

        return $value;
    }

    /**
     * setTranslations sets multiple locale values at once for a single attribute
     */
    public function setTranslations($key, array $translations)
    {
        foreach ($translations as $locale => $value) {
            $this->setTranslation($key, $locale, $value);
        }
    }

    //
    // Deleting translations
    //

    /**
     * forgetTranslation deletes a single translation row
     */
    public function forgetTranslation($key, $locale)
    {
        Db::table($this->getTranslateAttributeTable())
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey())
            ->where('locale', $locale)
            ->where('attribute', $key)
            ->delete();

        unset($this->translatableAttributes[$locale][$key]);
        unset($this->translatableOriginals[$locale][$key]);
    }

    /**
     * forgetTranslations deletes all translation rows for an attribute (all locales)
     */
    public function forgetTranslations($key)
    {
        Db::table($this->getTranslateAttributeTable())
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey())
            ->where('attribute', $key)
            ->delete();

        foreach ($this->translatableAttributes as $locale => &$data) {
            unset($data[$key]);
        }

        foreach ($this->translatableOriginals as $locale => &$data) {
            unset($data[$key]);
        }
    }

    /**
     * forgetAllTranslations deletes all translation rows for a locale ("unpublish French")
     */
    public function forgetAllTranslations($locale)
    {
        Db::table($this->getTranslateAttributeTable())
            ->where('model_type', $this->getMorphClass())
            ->where('model_id', $this->getKey())
            ->where('locale', $locale)
            ->delete();

        unset($this->translatableAttributes[$locale]);
        unset($this->translatableOriginals[$locale]);
    }

    //
    // Locale context
    //

    /**
     * setLocale overrides the locale context for this model instance
     */
    public function setLocale($locale)
    {
        $this->translatableContext = $locale;

        $this->fireEvent('model.translate.contextChange', [$locale]);

        return $this;
    }

    /**
     * getLocale returns the active locale (context override or site locale)
     */
    public function getLocale()
    {
        return $this->getTranslatableContext();
    }

    //
    // Dirty checking
    //

    /**
     * isTranslateDirty determines if the model or a given translated attribute
     * has been modified for a locale
     */
    public function isTranslateDirty($attribute = null, $locale = null)
    {
        $dirty = $this->getTranslateDirty($locale);

        if (is_null($attribute)) {
            return count($dirty) > 0;
        }

        return array_key_exists($attribute, $dirty);
    }

    /**
     * getTranslateDirty returns the translated attributes that have been changed
     * since last sync
     */
    public function getTranslateDirty($locale = null)
    {
        if (!$locale) {
            $locale = $this->getTranslatableContext();
        }

        if (!array_key_exists($locale, $this->translatableAttributes)) {
            return [];
        }

        // All dirty when no originals recorded
        if (!array_key_exists($locale, $this->translatableOriginals)) {
            return $this->translatableAttributes[$locale];
        }

        $dirty = [];
        foreach ($this->translatableAttributes[$locale] as $key => $value) {
            if (!array_key_exists($key, $this->translatableOriginals[$locale])) {
                $dirty[$key] = $value;
            }
            elseif ($value != $this->translatableOriginals[$locale][$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * getTranslatableOriginals gets the original values of the translated attributes
     */
    public function getTranslatableOriginals($locale = null)
    {
        if (!$locale) {
            return $this->translatableOriginals;
        }

        return $this->translatableOriginals[$locale] ?? null;
    }

    //
    // Data storage
    //

    /**
     * syncTranslatableAttributes restores the default language values on the model
     * and stores the translated values in the attributes table
     */
    protected function syncTranslatableAttributes()
    {
        // Store translations for each known locale. When the model has no key
        // yet (new record), defer until after insert assigns the primary key
        if ($this->getKey()) {
            $this->storeTranslatableBasicData();
        }
        else {
            $this->bindEventOnce('model.saveComplete', function() {
                $this->storeTranslatableBasicData();
            });
        }

        // Saving the default locale, no need to restore anything
        if (!$this->shouldTranslate()) {
            return;
        }

        // Restore translatable values to model originals so the base model
        // saves its own attributes correctly (not the translated values)
        $original = $this->getOriginal();
        $attributes = $this->getAttributes();
        $translatable = $this->getTranslatableAttributes();
        $originalValues = array_intersect_key($original, array_flip($translatable));
        $this->attributes = array_merge($attributes, $originalValues);
    }

    /**
     * storeTranslatableBasicData stores translations for each known dirty locale
     */
    protected function storeTranslatableBasicData()
    {
        $knownLocales = array_keys($this->translatableAttributes);
        foreach ($knownLocales as $locale) {
            if (!$this->isTranslateDirty(null, $locale)) {
                continue;
            }

            $this->fireEvent('model.translate.beforeSave', [$locale]);
            $this->storeTranslatableData($locale);
            $this->fireEvent('model.translate.afterSave', [$locale]);
        }
    }

    /**
     * storeTranslatableData saves translation data for a single locale using upsert
     */
    protected function storeTranslatableData($locale)
    {
        $dirty = $this->getTranslateDirty($locale);

        if (empty($dirty)) {
            return;
        }

        $isDefaultLocale = ($locale === $this->getTranslatableDefault());

        $rows = [];
        foreach ($dirty as $key => $value) {
            // For non-default locales, skip attributes whose value matches the
            // model's local attribute (the default locale value). No row = inherits
            // from default, so changes to the default automatically propagate.
            if (!$isDefaultLocale) {
                $defaultValue = $this->attributes[$key] ?? null;
                if ($value === $defaultValue) {
                    continue;
                }
            }

            // Serialize array values for storage
            $storeValue = is_array($value) ? json_encode($value) : $value;

            $rows[] = [
                'model_type' => $this->getMorphClass(),
                'model_id' => $this->getKey(),
                'locale' => $locale,
                'attribute' => $key,
                'value' => $storeValue,
            ];
        }

        if (empty($rows)) {
            return;
        }

        Db::table($this->getTranslateAttributeTable())->upsert(
            $rows,
            ['model_type', 'model_id', 'locale', 'attribute'],
            ['value']
        );
    }

    /**
     * loadTranslatableData loads translation data for a locale, using the eager-loaded
     * relationship when available, falling back to a direct query
     */
    protected function loadTranslatableData($locale)
    {
        if ($this->relationLoaded('translations')) {
            $rows = $this->translations
                ->where('locale', $locale)
                ->pluck('value', 'attribute')
                ->toArray();
        }
        else {
            $rows = Db::table($this->getTranslateAttributeTable())
                ->where('model_type', $this->getMorphClass())
                ->where('model_id', $this->getKey())
                ->where('locale', $locale)
                ->pluck('value', 'attribute')
                ->toArray();
        }

        $this->translatableAttributes[$locale] = $rows;
        $this->translatableOriginals[$locale] = $rows;
    }

    //
    // Query scopes
    //

    /**
     * scopeWhereTranslation adds a where clause for a translated attribute
     */
    public function scopeWhereTranslation($query, $key, $locale, $value, $operator = '=')
    {
        return $query->whereExists(function ($q) use ($key, $locale, $value, $operator) {
            $table = $this->getTranslateAttributeTable();

            $q->select(Db::raw(1))
                ->from($table)
                ->whereColumn($table . '.model_id', $this->getQualifiedKeyName())
                ->where($table . '.model_type', $this->getMorphClass())
                ->where($table . '.locale', $locale)
                ->where($table . '.attribute', $key)
                ->where($table . '.value', $operator, $value);
        });
    }

    /**
     * scopeOrderByTranslation adds an order by clause for a translated attribute
     */
    public function scopeOrderByTranslation($query, $key, $locale, $direction = 'asc')
    {
        $table = $this->getTranslateAttributeTable();
        $alias = 'translate_order_' . $key;

        return $query
            ->leftJoin($table . ' as ' . $alias, function ($join) use ($alias, $key, $locale) {
                $join->on($alias . '.model_id', '=', $this->getQualifiedKeyName())
                    ->where($alias . '.model_type', '=', $this->getMorphClass())
                    ->where($alias . '.locale', '=', $locale)
                    ->where($alias . '.attribute', '=', $key);
            })
            ->orderBy($alias . '.value', $direction);
    }

    /**
     * scopeWithTranslation eager loads translations for a single locale
     */
    public function scopeWithTranslation($query, $locale = null)
    {
        if ($locale === null) {
            $locale = $this->getTranslatableContext();
        }

        return $query->with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale);
        }]);
    }

    /**
     * scopeWithTranslations eager loads all translations (all locales)
     */
    public function scopeWithTranslations($query)
    {
        return $query->with('translations');
    }

    //
    // Helpers
    //

    /**
     * getTranslateAttributeModelClass returns the model class used for translations.
     * Resolved via the 'translate.attribute' container binding when available,
     * falling back to the base TranslateAttribute model. Override per-model
     * to use a custom translation table.
     */
    public function getTranslateAttributeModelClass()
    {
        if (App::bound('core.translate.attribute')) {
            return App::make('core.translate.attribute');
        }

        return \October\Rain\Database\Models\TranslateAttribute::class;
    }

    /**
     * getTranslateAttributeTable returns the table name for translation storage
     */
    public function getTranslateAttributeTable()
    {
        $modelClass = $this->getTranslateAttributeModelClass();

        return (new $modelClass)->getTable();
    }
}
