<?php

namespace October\Rain\Database\Concerns;

trait GuardsAttributes
{
    /**
     * Determine if the given key is guarded.
     *
     * This is an override of https://github.com/laravel/framework/commit/897d107775737a958dbd0b2f3ea37877c7526371
     * and allows fields that don't exist in the database to be filled if they aren't specified as "guarded", under
     * the pretense that they are handled in a special manner - ie. in the `beforeSave` event.
     *
     * @param  string  $key
     * @return bool
     */
    public function isGuarded($key)
    {
        if (empty($this->getGuarded())) {
            return false;
        }

        return $this->getGuarded() == ['*'] ||
               ! empty(preg_grep('/^'.preg_quote($key).'$/i', $this->getGuarded()));
    }
}
