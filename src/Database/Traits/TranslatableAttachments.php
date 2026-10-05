<?php namespace October\Rain\Database\Traits;

use Exception;

/**
 * TranslatableAttachments stores the files of attachment relations listed in $translatable per locale.
 *
 * Usage:
 *
 *     use \October\Rain\Database\Traits\Translatable;
 *     use \October\Rain\Database\Traits\TranslatableAttachments;
 *
 *     public $translatable = ['name', 'image'];
 *
 *     public $attachOne = ['image' => \System\Models\File::class];
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait TranslatableAttachments
{
    /**
     * initializeTranslatableAttachments trait for a model
     */
    public function initializeTranslatableAttachments()
    {
        if (!method_exists($this, 'shouldTranslate')) {
            throw new Exception(sprintf(
                'The TranslatableAttachments trait in %s requires the Translatable trait.',
                static::class
            ));
        }

        // Swap attachment relations once extensions have defined them
        $this->bindEvent('model.afterInit', function() {
            $this->defineTranslatableAttachments();
        });

        // Loaded attachments belong to the previous locale
        $this->bindEvent('model.translate.contextChange', function() {
            foreach ($this->getTranslatableAttachments() as $key) {
                $this->unsetRelation($key);
            }
        });

        // Remove the attachments of every locale with the record
        $this->bindEvent('model.afterDelete', function() {
            $this->deleteTranslatableAttachments();
        });
    }

    /**
     * getTranslatableAttachments returns the translatable attributes defined as file attachment relations.
     */
    public function getTranslatableAttachments(): array
    {
        return array_values(array_filter($this->getTranslatableAttributes(), function ($key) {
            return in_array($this->getRelationType($key), ['attachOne', 'attachMany']);
        }));
    }

    /**
     * hasAttachmentTranslation checks if a locale stores files of its own for an attachment, defaulting to the active locale.
     */
    public function hasAttachmentTranslation($key, $locale = null): bool
    {
        if (!$this->isTranslatableAttachment($key)) {
            return false;
        }

        return $this->$key()->newAttachmentQuery()
            ->where('field', $this->getTranslatableAttachmentField($key, $locale ?? $this->getLocale()))
            ->exists();
    }

    /**
     * getTranslatedAttachmentLocales returns the non-default locales that store files of their own for an attachment.
     */
    public function getTranslatedAttachmentLocales($key): array
    {
        if (!$this->isTranslatableAttachment($key)) {
            return [];
        }

        $locales = [];

        foreach ($this->$key()->newAttachmentQuery()->pluck('field') as $field) {
            if ($locale = $this->getTranslatableAttachmentLocale($key, $field)) {
                $locales[] = $locale;
            }
        }

        return array_values(array_unique($locales));
    }

    /**
     * forgetAttachmentTranslation removes the files stored for a locale so it falls back to the default files.
     */
    public function forgetAttachmentTranslation($key, $locale)
    {
        $this->forgetTranslatableAttachmentFiles($key, [$locale]);
    }

    /**
     * forgetAttachmentTranslations removes the files stored for every non-default locale of an attachment.
     */
    public function forgetAttachmentTranslations($key)
    {
        $this->forgetTranslatableAttachmentFiles($key);
    }

    /**
     * isTranslatableAttachment checks if a translatable attribute is a file attachment relation.
     */
    protected function isTranslatableAttachment($key): bool
    {
        return in_array($key, $this->getTranslatableAttachments());
    }

    /**
     * defineTranslatableAttachments swaps translatable attachment relations for ones that store files per locale.
     */
    protected function defineTranslatableAttachments()
    {
        $relationClasses = [
            'attachOne' => \October\Rain\Database\Relations\TranslatableAttachOne::class,
            'attachMany' => \October\Rain\Database\Relations\TranslatableAttachMany::class
        ];

        foreach ($this->getTranslatableAttachments() as $key) {
            $type = $this->getRelationType($key);

            $definition = (array) $this->{$type}[$key];
            $definition['relationClass'] ??= $relationClasses[$type];

            $this->{$type}[$key] = $definition;
        }
    }

    /**
     * getTranslatableAttachmentField returns the field column value for an attachment in a locale.
     */
    protected function getTranslatableAttachmentField($key, $locale): string
    {
        return $locale === $this->getTranslatableDefault() ? $key : $key . ':' . $locale;
    }

    /**
     * getTranslatableAttachmentLocale returns the locale from a field column value, or null for the default locale.
     */
    protected function getTranslatableAttachmentLocale($key, $field): ?string
    {
        $prefix = $key . ':';

        return str_starts_with((string) $field, $prefix) ? substr($field, strlen($prefix)) : null;
    }

    /**
     * getTranslatableAttachmentFiles returns the attachment files for the default locale and every other locale.
     */
    protected function getTranslatableAttachmentFiles($key)
    {
        return $this->$key()->newAttachmentQuery()->get()->filter(function ($file) use ($key) {
            return $file->field === $key || $this->getTranslatableAttachmentLocale($key, $file->field) !== null;
        });
    }

    /**
     * forgetTranslatableAttachmentFiles removes the files stored for the given locales, or every non-default locale.
     */
    protected function forgetTranslatableAttachmentFiles($key, ?array $locales = null)
    {
        if (!$this->isTranslatableAttachment($key)) {
            return;
        }

        $files = $this->getTranslatableAttachmentFiles($key)->filter(function ($file) use ($key, $locales) {
            $locale = $this->getTranslatableAttachmentLocale($key, $file->field);

            return $locale !== null && ($locales === null || in_array($locale, $locales));
        });

        $this->removeTranslatableAttachmentFiles($key, $files);

        $this->unsetRelation($key);
    }

    /**
     * deleteTranslatableAttachments removes the attachment files of every locale after the record is deleted.
     */
    protected function deleteTranslatableAttachments()
    {
        // Soft deleted records keep their attachments for restoring
        if (method_exists($this, 'isSoftDelete') && $this->isSoftDeleteEnabled() && $this->isSoftDelete()) {
            return;
        }

        $useMultisite = $this->isClassInstanceOf(\October\Contracts\Database\MultisiteInterface::class) && $this->isMultisiteEnabled();

        foreach ($this->getTranslatableAttachments() as $key) {
            if ($useMultisite && !$this->canDeleteMultisiteRelation($key, $this->getRelationType($key))) {
                continue;
            }

            $this->removeTranslatableAttachmentFiles($key, $this->getTranslatableAttachmentFiles($key));
        }
    }

    /**
     * removeTranslatableAttachmentFiles deletes or orphans files following the relation delete option.
     */
    protected function removeTranslatableAttachmentFiles($key, $files)
    {
        if ($files->isEmpty()) {
            return;
        }

        if ($this->getRelationDefinition($key)['delete'] ?? false) {
            $files->each(function ($file) {
                $file->delete();
            });
            return;
        }

        $this->$key()->getRelated()->newQuery()->whereKey($files->modelKeys())->update([
            'attachment_id' => null,
            'attachment_type' => null,
            'field' => null
        ]);
    }
}
