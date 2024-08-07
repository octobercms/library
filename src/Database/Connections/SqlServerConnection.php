<?php namespace October\Rain\Database\Connections;

use Illuminate\Database\SqlServerConnection as SqlServerConnectionBase;

/**
 * SqlServerConnectionBase implements connection extension
 */
class SqlServerConnection extends SqlServerConnectionBase
{
    use \October\Rain\Database\Connections\ExtendsConnection;
}
