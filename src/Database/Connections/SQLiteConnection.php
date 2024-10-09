<?php namespace October\Rain\Database\Connections;

use Illuminate\Database\SQLiteConnection as SQLiteConnectionBase;

/**
 * SQLiteConnection implements connection extension
 */
class SQLiteConnection extends SQLiteConnectionBase
{
    use \October\Rain\Database\Connections\ExtendsConnection;
}
