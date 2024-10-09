<?php namespace October\Rain\Database\Connections;

use Illuminate\Database\MySqlConnection as MySqlConnectionBase;

/**
 * MySqlConnection implements connection extension
 */
class MySqlConnection extends MySqlConnectionBase
{
    use \October\Rain\Database\Connections\ExtendsConnection;
}
