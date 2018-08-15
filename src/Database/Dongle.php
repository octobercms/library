<?php namespace October\Rain\Database;

/**
 * Database driver dongle
 *
 * This class uses regex to convert MySQL to various other drivers.
 */
class Dongle
{
    /**
     * @var DB Database helper object
     */
    protected $db;

    /**
     * @var string Driver to convert to: mysql, sqlite, pgsql, sqlsrv, postgis.
     */
    protected $driver;

    /**
     * @var bool Used to determine whether strict mode has been disabled.
     */
    protected $strictModeDisabled;

    /**
     * Constructor.
     */
    public function __construct($driver = 'mysql', $db = null)
    {
        $this->db = $db;
        $this->driver = $driver;
    }

    /**
     * Transforms and executes a raw SQL statement
     * @param  string $sql
     * @return mixed
     */
    public function raw($sql)
    {
        return $this->db->raw($this->parse($sql));
    }

    /**
     * Transforms an SQL statement to match the active driver.
     * @param  string $sql
     * @return string
     */
    public function parse($sql)
    {
        $sql = $this->parseGroupConcat($sql);
        $sql = $this->parseConcat($sql);
        $sql = $this->parseIfNull($sql);
        $sql = $this->parseBooleanExpression($sql);
        return $sql;
    }

    /**
     * Transforms GROUP_CONCAT statement.
     * @param  string $sql
     * @return string
     */
    public function parseGroupConcat($sql)
    {
        $result = preg_replace_callback('/group_concat\((.+)\)/i', function($matches){
            if (!isset($matches[1])) {
                return $matches[0];
            }

            switch ($this->driver) {
                default:
                case 'mysql':
                    return $matches[0];

                case 'pgsql':
                case 'postgis':
                case 'sqlite':
                case 'sqlsrv':
                    return str_ireplace(' separator ', ', ', $matches[0]);
            }
        }, $sql);

        if ($this->driver == 'pgsql' || $this->driver == 'postgis') {
            $result = preg_replace("/\\(([]a-zA-Z\\-\\_\\.]+)\\,/i", "($1::VARCHAR,", $result);
            $result = str_ireplace('group_concat(', 'string_agg(', $result);
        }

        /*
         * Requires https://groupconcat.codeplex.com/
         */
        if ($this->driver == 'sqlsrv') {
            $result = str_ireplace('group_concat(', 'dbo.GROUP_CONCAT_D(', $result);
        }

        return $result;
    }

    /**
     * Transforms CONCAT statement.
     * @param  string $sql
     * @return string
     */
    public function parseConcat($sql)
    {
        return preg_replace_callback('/(?:group_)?concat\((.+)\)(?R)?/i', function($matches){
            if (!isset($matches[1])) {
                return $matches[0];
            }

            // This is a group_concat() so ignore it
            if (strpos($matches[0], 'group_') === 0) {
                return $matches[0];
            }

            $concatFields = array_map('trim', explode(',', $matches[1]));

            switch ($this->driver) {
                default:
                case 'mysql':
                    return $matches[0];

                case 'pgsql':
                case 'postgis':
                case 'sqlite':
                    return implode(' || ', $concatFields);
            }
        }, $sql);
    }

    /**
     * Transforms IFNULL statement.
     * @param  string $sql
     * @return string
     */
    public function parseIfNull($sql)
    {
        if ($this->driver == 'pgsql' || $this->driver == 'postgis') {
            return str_ireplace('ifnull(', 'coalesce(', $sql);
        }

        if ($this->driver == 'sqlsrv') {
            return str_ireplace('ifnull(', 'isnull(', $sql);
        }

        return $sql;
    }

    /**
     * Transforms true|false expressions in a statement.
     * @param  string $sql
     * @return string
     */
    public function parseBooleanExpression($sql)
    {
        if ($this->driver != 'sqlite') {
            return $sql;
        }

        return preg_replace_callback('/(\w+)\s*(=|<>)\s*(true|false)($|\s)/i', function ($matches) {
            array_shift($matches);
            $space = array_pop($matches);
            $matches[2] = $matches[2] == 'true' ? 1 : 0;
            return implode(' ', $matches) . $space;
        }, $sql);
    }

    /**
     * Some drivers require same-type comparisons.
     * @param  string $sql
     * @return string
     */
    public function cast($sql, $asType = 'INTEGER')
    {
        if ($this->driver != 'pgsql' && $this->driver != 'postgis') {
            return $sql;
        }

        return 'CAST('.$sql.' AS '.$asType.')';
    }

    /**
     * Alters a table's TIMESTAMP field(s) to be nullable and converts existing values.
     *
     * This is needed to transition from older Laravel code that set DEFAULT 0, which is an
     * invalid date in newer MySQL versions where NO_ZERO_DATE is included in strict mode.
     *
     * @param string        $table
     * @param string|array  $columns Column name(s). Defaults to ['created_at', 'updated_at']
     */
    public function convertTimestamps($table, $columns = null)
    {
        if ($this->driver != 'mysql') {
            return;
        }

        if (!is_array($columns)) {
            $columns = is_null($columns) ? ['created_at', 'updated_at'] : [$columns];
        }
        
        $prefixedTable = $this->getTablePrefix() . $table;

        foreach ($columns as $column) {
            $this->db->statement("ALTER TABLE {$prefixedTable} MODIFY `{$column}` TIMESTAMP NULL DEFAULT NULL");
            $this->db->update("UPDATE {$prefixedTable} SET {$column} = null WHERE {$column} = 0");
        }
    }

    /**
     * Used to disable strict mode during migrations
     */
    public function disableStrictMode()
    {
        if ($this->driver != 'mysql') {
            return;
        }

        if ($this->strictModeDisabled || $this->db->getConfig('strict') === false) {
            return;
        }

        $this->db->statement("SET @@SQL_MODE=''");
        $this->strictModeDisabled = true;
    }

    /**
     * Returns the driver name as a string, eg: pgsql
     * @return string
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get the table prefix.
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->db->getTablePrefix();
    }
}
