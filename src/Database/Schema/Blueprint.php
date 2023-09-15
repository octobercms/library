<?php namespace October\Rain\Database\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;

/**
 * Blueprint proxy class
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Blueprint extends BaseBlueprint
{
    /**
     * multisite adds columns used by the Multisite trait
     *
     * @param  string  $column
     * @param  string|null  $indexName
     * @return void
     */
    public function multisite($column = 'site_id', $indexName = null)
    {
        $this->unsignedBigInteger($column)->nullable();

        $this->unsignedBigInteger('site_root_id')->nullable();

        $this->index([$column, 'site_root_id'], $indexName);
    }
}
