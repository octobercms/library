<?php namespace October\Rain\Database\Traits;

/**
 * SluggableTree trait creates structured slugs, called full slugs. Calculating full slugs
 * must be performed externally since it involves expensive lookups. The model is assumed
 * to have two relations defined: parent, children.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait SluggableTree
{
    /**
     * fullSlugAttributes calculates full slugs for this model and all descendants
     * @return void
     */
    public function fullSlugAttributes()
    {
        $this->setFullSluggedValue($this);
    }

    /**
     * setFullSluggedValue will set the fullslug value on a model and recurse
     * into children. For translatable models, the Translatable trait intercepts
     * attribute access for the active locale automatically.
     */
    protected function setFullSluggedValue($model)
    {
        $fullslugAttr = $this->getFullSluggableFullSlugColumnName();
        $proposedSlug = $this->getFullSluggableAttributeValue($model);

        if ($model->{$fullslugAttr} !== $proposedSlug) {
            $model->{$fullslugAttr} = $proposedSlug;
            $model->saveQuietly(['force' => true]);
        }

        $this->setFullSluggedTranslatedValues($model);

        if ($children = $model->children) {
            foreach ($children as $child) {
                $this->setFullSluggedValue($child);
            }
        }
    }

    /**
     * getFullSluggableAttributeValue builds the fullslug by walking up the
     * parent chain using the model's slug attribute
     */
    protected function getFullSluggableAttributeValue($model, $fullslug = '')
    {
        $slugAttr = $this->getFullSluggableSlugColumnName();
        $fullslug = $model->{$slugAttr} . '/' . $fullslug;

        if ($parent = $model->parent()->withoutGlobalScopes()->first()) {
            $fullslug = $this->getFullSluggableAttributeValue($parent, $fullslug);
        }

        return rtrim($fullslug, '/');
    }

    /**
     * getFullSluggableFullSlugColumnName gets the name of the "fullslug" column.
     * @return string
     */
    public function getFullSluggableFullSlugColumnName()
    {
        return defined('static::FULLSLUG') ? static::FULLSLUG : 'fullslug';
    }

    /**
     * getFullSluggableSlugColumnName gets the name of the "slug" column.
     * @return string
     */
    public function getFullSluggableSlugColumnName()
    {
        return defined('static::SLUG') ? static::SLUG : 'slug';
    }

    //
    // Translatable compatibility
    //

    /**
     * setFullSluggedTranslatedValues recomputes the fullslug for every locale with
     * slug translations on the model or its ancestor chain
     */
    protected function setFullSluggedTranslatedValues($model)
    {
        if (!method_exists($model, 'getTranslatedLocales')) {
            return;
        }

        $fullslugAttr = $this->getFullSluggableFullSlugColumnName();
        $defaultValue = $model->getTranslation($fullslugAttr, $model->getTranslatableDefault());
        $excludeLocales = [$model->getTranslatableDefault(), $model->getTranslatableContext()];
        $wantSave = false;

        foreach ($this->getFullSluggableTranslatedLocales($model) as $locale) {
            if (in_array($locale, $excludeLocales)) {
                continue;
            }

            $proposedSlug = $this->getFullSluggableTranslatedAttributeValue($model, $locale);

            if ($model->getTranslation($fullslugAttr, $locale) === $proposedSlug) {
                continue;
            }

            // A value matching the default locale inherits by omission
            if ($proposedSlug === $defaultValue) {
                $model->forgetTranslation($fullslugAttr, $locale);
            }
            else {
                $model->setTranslation($fullslugAttr, $locale, $proposedSlug);
                $wantSave = true;
            }
        }

        if ($wantSave) {
            $model->saveQuietly(['force' => true]);
        }
    }

    /**
     * getFullSluggableTranslatedAttributeValue builds the fullslug for a locale by
     * walking up the parent chain, falling back to default slugs when untranslated
     */
    protected function getFullSluggableTranslatedAttributeValue($model, $locale, $fullslug = '')
    {
        $slugAttr = $this->getFullSluggableSlugColumnName();
        $fullslug = $model->getTranslation($slugAttr, $locale) . '/' . $fullslug;

        if ($parent = $model->parent()->withoutGlobalScopes()->first()) {
            $fullslug = $this->getFullSluggableTranslatedAttributeValue($parent, $locale, $fullslug);
        }

        return rtrim($fullslug, '/');
    }

    /**
     * getFullSluggableTranslatedLocales returns locales with slug translations on
     * the model or any of its ancestors
     */
    protected function getFullSluggableTranslatedLocales($model)
    {
        $slugAttr = $this->getFullSluggableSlugColumnName();
        $locales = $model->getTranslatedLocales($slugAttr);

        if ($parent = $model->parent()->withoutGlobalScopes()->first()) {
            $locales = array_merge($locales, $this->getFullSluggableTranslatedLocales($parent));
        }

        return array_unique($locales);
    }
}
