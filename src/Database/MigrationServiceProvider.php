<?php namespace October\Rain\Database;

use Illuminate\Database\MigrationServiceProvider as MigrationServiceProviderBase;

class MigrationServiceProvider extends MigrationServiceProviderBase
{
    /**
     * @var array commands to be registered
     */
    protected $commands = [];
}
