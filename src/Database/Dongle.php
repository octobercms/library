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
     * @var string Driver to convert to: mysql, sqlite, pgsql, sqlsrv.
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
        return $sql;
    }

    /**
     * Transforms GROUP_CONCAT statement.
     * @param  string $sql
     * @return string
     */
    public function parseGroupConcat($sql)
    {
        return preg_replace_callback('/group_concat\(([^)]+)\)/i', function($matches){
            if (!isset($matches[1]))
                return $matches[0];

            switch ($this->driver) {
                default:
                case 'mysql':
                    return $matches[0];

                case 'sqlite':
                    return str_ireplace(' separator ', ', ', $matches[0]);
            }
        }, $sql);
    }

    /**
     * Transforms CONCAT statement.
     * @param  string $sql
     * @return string
     */
    public function parseConcat($sql)
    {
        return preg_replace_callback('/(?:group_)?concat\(([^)]+)\)/i', function($matches){
            if (!isset($matches[1]))
                return $matches[0];

            // This is a group_concat() so ignore it
            if (strpos($matches[0], 'group_') === 0)
                return $matches[0];

            $concatFields = array_map('trim', explode(',', $matches[1]));

            switch ($this->driver) {
                default:
                case 'mysql':
                    return $matches[0];

                case 'sqlite':
                    return implode(' || ', $concatFields);
            }
        }, $sql);
    }

}
