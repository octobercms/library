<?php namespace October\Rain\Database\Concerns;

use October\Rain\Support\Str;

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
        $guarded = $this->getGuarded();
        // Nothing's guarded so just return early
        if (empty($guarded) || $guarded === ['*']) {
            return false;
        }
        // Normalize the variables for comparison
        $key = trim(strtolower($key));
        $guarded = array_map(function ($column) {
            return trim(strtolower($column));
        }, $guarded);

        // JSON columns are tricksy, we only guard base level columns though
        if (strpos($key, '->') !== false) {
            $key = Str::before($key, '->');
        }

        return in_array($key, $guarded);
    }
}
