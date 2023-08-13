<?php namespace October\Rain\Database;

/**
 * Dongle driver for database that uses regex to convert MySQL to various other drivers.
 */
class Dongle
{
    /**
     * @var \Db db helper object
     */
    protected $db;

    /**
     * @var string driver to convert to: mysql, sqlite, pgsql, sqlsrv, postgis
     */
    protected $driver;

    /**
     * @var bool strictModeDisabled used to determine whether strict mode has been disabled
     */
    protected $strictModeDisabled;

    /**
     * __construct
     */
    public function __construct($driver = 'mysql', $db = null)
    {
        $this->db = $db;
        $this->driver = $driver;
    }

    /**
     * raw transforms and executes a raw SQL statement
     */
    public function raw(string $sql, array $params = null)
    {
        return $this->db->raw($this->parse($sql, $params));
    }

    /**
     * rawValue converts a raw expression to a string
     *
     * @todo Can be refactored if Laravel >= 10
     */
    public function rawValue($sql): string
    {
        if (interface_exists(\Illuminate\Contracts\Database\Query\Expression::class)) {
            return $this->db->raw($sql)->getValue($this->db->connection()->getQueryGrammar());
        }

        return (string) $this->db->raw($sql);
    }

    /**
     * parse transforms an SQL statement to match the active driver. If params are supplied,
     * replaces :column_name with array value without requiring a list of names.
     * Example: custom_country_id = :country_id → custom_country_id = 7
     */
    public function parse(string $sql, array $params = null): string
    {
        if (is_array($params) && preg_match_all('/\:([\w]+)/', $sql, $matches)) {
            $sql = $this->parseValues($sql, $params, $matches[1]);
        }

        $sql = $this->parseGroupConcat($sql);

        $sql = $this->parseConcat($sql);

        $sql = $this->parseIfNull($sql);

        $sql = $this->parseBooleanExpression($sql);

        return $sql;
    }

    /**
     * parseValues will protect parameter values by quoting them or handling safe values. Eg:
     *
     *     username = :value   → username = 'foobar'
     *     username = :value%  → username = 'foobar%'
     *     username = %:value  → username = '%foobar'
     *     username = %:value% → username = '%foobar%'
     */
    public function parseValues(string $sql, array $data, array $paramNames)
    {
        $toReplace = [];

        foreach ($paramNames as $param) {
            $parsedValue = array_key_exists($param, $data) ? $data[$param] : null;

            if (is_string($parsedValue)) {
                $pdo = $this->db->getPdo();
                $toReplace['%:' . $param . '%'] = $pdo->quote('%' . $parsedValue . '%');
                $toReplace['%:' . $param] = $pdo->quote('%' . $parsedValue);
                $toReplace[':' . $param . '%'] = $pdo->quote($parsedValue . '%');
                $toReplace[':' . $param] = $pdo->quote($parsedValue);
            } else {
                if (is_null($parsedValue)) {
                    $parsedValue = 'NULL';
                } elseif (is_numeric($parsedValue)) {
                    $parsedValue = +$parsedValue;
                } else {
                    $parsedValue = "''";
                }

                $toReplace['%:' . $param . '%'] = $parsedValue;
                $toReplace['%:' . $param] = $parsedValue;
                $toReplace[':' . $param . '%'] = $parsedValue;
                $toReplace[':' . $param] = $parsedValue;
            }
        }

        return strtr($sql, $toReplace);
    }

    /**
     * parseGroupConcat transforms GROUP_CONCAT statement
     */
    public function parseGroupConcat(string $sql): string
    {
        if ($this->driver === 'mysql') {
            return $sql;
        }

        $result = preg_replace_callback('/group_concat\((.+)\)/i', function ($matches) {
            if (!isset($matches[1])) {
                return $matches[0];
            }

            switch ($this->driver) {
                default:
                    return $matches[0];

                case 'pgsql':
                case 'postgis':
                case 'sqlite':
                case 'sqlsrv':
                    return str_ireplace(' separator ', ', ', $matches[0]);
            }
        }, $sql);

        if ($this->driver === 'pgsql' || $this->driver === 'postgis') {
            $result = preg_replace("/\\(([]a-zA-Z\\-\\_\\.]+)\\,/i", "($1::VARCHAR,", $result);
            $result = str_ireplace('group_concat(', 'string_agg(', $result);
        }

        /*
         * Requires https://groupconcat.codeplex.com/
         */
        if ($this->driver === 'sqlsrv') {
            $result = str_ireplace('group_concat(', 'dbo.GROUP_CONCAT_D(', $result);
        }

        return $result;
    }

    /**
     * parseConcat transforms CONCAT statement
     */
    public function parseConcat(string $sql): string
    {
        if ($this->driver === 'mysql') {
            return $sql;
        }

        // Pre process special characters inside quotes
        $charComma = 'X___COMMA_CHAR___X';
        $result = preg_replace_callback("/'(.*?[^\\\\])'/i", function ($matches) use ($charComma) {
            return str_replace(',', $charComma, $matches[0]);
        }, $sql);

        // Convert concat() to pipe (||) syntax
        $result = preg_replace_callback('/(?:group_)?concat\((.+)\)(?R)?/i', function ($matches) {
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
                    return $matches[0];

                case 'pgsql':
                case 'postgis':
                case 'sqlite':
                    return implode(' || ', $concatFields);
            }
        }, $result);

        // Replace special characters back to their originals
        $result = str_replace($charComma, ',', $result);

        return $result;
    }

    /**
     * parseIfNull transforms IFNULL statement
     */
    public function parseIfNull(string $sql): string
    {
        if ($this->driver === 'pgsql' || $this->driver === 'postgis') {
            return str_ireplace('ifnull(', 'coalesce(', $sql);
        }

        if ($this->driver === 'sqlsrv') {
            return str_ireplace('ifnull(', 'isnull(', $sql);
        }

        return $sql;
    }

    /**
     * parseBooleanExpression transforms true|false expressions in a statement
     */
    public function parseBooleanExpression(string $sql): string
    {
        if ($this->driver !== 'sqlite' && $this->driver !== 'sqlsrv') {
            return $sql;
        }

        return preg_replace_callback('/(\w+)\s*(=|<>)\s*(true|false)($|\s)/i', function ($matches) {
            array_shift($matches);
            $space = array_pop($matches);
            $matches[2] = $matches[2] === 'true' ? 1 : 0;
            return implode(' ', $matches) . $space;
        }, $sql);
    }

    /**
     * cast for some drivers that require same-type comparisons
     */
    public function cast(string $sql, $asType = 'INTEGER'): string
    {
        if ($this->driver !== 'pgsql' && $this->driver !== 'postgis') {
            return $sql;
        }

        return 'CAST(' . $sql . ' AS ' . $asType . ')';
    }

    /**
     * convertTimestamps alters a table's TIMESTAMP field(s) to be nullable and converts existing values.
     *
     * This is needed to transition from older Laravel code that set DEFAULT 0, which is an
     * invalid date in newer MySQL versions where NO_ZERO_DATE is included in strict mode.
     *
     * @param string $table
     * @param string|array $columns Column name(s). Defaults to ['created_at', 'updated_at']
     */
    public function convertTimestamps($table, $columns = null)
    {
        if ($this->driver !== 'mysql') {
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
     * disableStrictMode is used to disable strict mode during migration
     */
    public function disableStrictMode()
    {
        if ($this->driver !== 'mysql') {
            return;
        }

        if ($this->strictModeDisabled || $this->db->getConfig('strict') === false) {
            return;
        }

        $this->db->statement("SET @@SQL_MODE=''");
        $this->strictModeDisabled = true;
    }

    /**
     * getDriver returns the driver name as a string, eg: pgsql
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * getTablePrefix gets the table prefix
     */
    public function getTablePrefix(): string
    {
        return $this->db->getTablePrefix();
    }

    /**
     * @deprecated use parse with second argument
     */
    public function parseParams(string $sql, array $params)
    {
        return $this->parse($sql, $params);
    }
}
