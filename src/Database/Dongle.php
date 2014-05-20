<?php namespace October\Rain\Database;

/**
 * Database driver dongle
 *
 * This class uses regex to convert MySQL to various other drivers.
 */
class Dongle
{

    protected $db;
    protected $driver;

    public function __construct($driver = 'mysql', $db = null)
    {
        $this->db = $db;
        $this->driver = $driver;
    }

    public function raw($sql)
    {
        return $this->db->raw($this->parse($sql));
    }

    public function parse($sql)
    {
        $sql = $this->parseGroupConcat($sql);
        $sql = $this->parseConcat($sql);
        return $sql;
    }

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
