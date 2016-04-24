<?php namespace October\Rain\Database\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;

class Blueprint extends BaseBlueprint
{
    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @return void
     */
    public function nullableTimestamps()
    {
        return $this->timestamps();
    }

    /**
     * Add nullable creation and update timestamps to the table.
     *
     * @return void
     */
    public function timestamps()
    {
        $this->timestamp('created_at')->nullable();

        $this->timestamp('updated_at')->nullable();
    }

    /**
     * Add creation and update timestampTz columns to the table.
     *
     * @return void
     */
    public function timestampsTz()
    {
        $this->timestampTz('created_at')->nullable();

        $this->timestampTz('updated_at')->nullable();
    }
}
