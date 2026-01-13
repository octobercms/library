<?php namespace October\Rain\Database\Connections;

use Illuminate\Database\MariaDbConnection as MariaDbConnectionBase;

/**
 * MariaDbConnection implements connection extension
 */
class MariaDbConnection extends MariaDbConnectionBase
{
    use \October\Rain\Database\Connections\ExtendsConnection;
}
