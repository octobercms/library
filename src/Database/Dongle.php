<?php namespace October\Rain\Database;

use Exception;

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
     * Constructor.
     */
    public function __construct($driver = 'mysql', $db = null)
    {
        $this->db = $db;
        $this->driver = $driver;
    }

    /**
     * Helper method, softly checks if a database is present.
     * @return boolean
     */
    public function hasDatabase()
    {
        try {
            $this->db->connection()->getDatabaseName();
        }
        catch (Exception $ex) {
            return false;
        }

        return true;
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
        return $sql;
    }

    /**
     * Transforms GROUP_CONCAT statement.
     * @param  string $sql
     * @return string
     */
    public function parseGroupConcat($sql)
    {
        $result = preg_replace_callback('/group_concat\(([^)]+)\)/i', function($matches){
            if (!isset($matches[1]))
                return $matches[0];

            switch ($this->driver) {
                default:
                case 'mysql':
                    return $matches[0];

                case 'pgsql':
                case 'postgis':
                case 'sqlite':
                    return str_ireplace(' separator ', ', ', $matches[0]);
            }
        }, $sql);

        if ($this->driver == 'pgsql' || $this->driver == 'postgis') {
            $result = preg_replace("/\\(([]a-zA-Z\\-\\_]+)\\,/i", "($1::VARCHAR,", $result);
            $result = str_ireplace('group_concat(', 'string_agg(', $result);
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
        return preg_replace_callback('/(?:group_)?concat\(([^)]+)\)(?R)?/i', function($matches){
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
        if ($this->driver != 'pgsql' && $this->driver != 'postgis') {
            return $sql;
        }

        return str_ireplace('ifnull(', 'coalesce(', $sql);
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
