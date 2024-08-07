<?php namespace October\Rain\Database\Connections;

use Illuminate\Database\PostgresConnection as PostgresConnectionBase;

/**
 * PostgresConnection implements connection extension
 */
class PostgresConnection extends PostgresConnectionBase
{
    use \October\Rain\Database\Connections\ExtendsConnection;
}
