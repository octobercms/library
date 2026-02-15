<?php

use October\Rain\Database\Dongle;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 * @Iterations(5)
 */
class DatabaseBench
{
    /**
     * @var Dongle dongle for MySQL
     */
    protected $dongleMysql;

    /**
     * @var Dongle dongle for SQLite
     */
    protected $dongleSqlite;

    /**
     * @var Dongle dongle for PostgreSQL
     */
    protected $donglePgsql;

    /**
     * init
     */
    public function init()
    {
        $this->dongleMysql = new Dongle('mysql');
        $this->dongleSqlite = new Dongle('sqlite');
        $this->donglePgsql = new Dongle('pgsql');
    }

    /**
     * @Subject
     */
    public function benchParseConcatMysql()
    {
        $this->dongleMysql->parseConcat("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM users");
    }

    /**
     * @Subject
     */
    public function benchParseConcatSqlite()
    {
        $this->dongleSqlite->parseConcat("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM users");
    }

    /**
     * @Subject
     */
    public function benchParseConcatPgsql()
    {
        $this->donglePgsql->parseConcat("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM users");
    }

    /**
     * @Subject
     */
    public function benchParseGroupConcatMysql()
    {
        $this->dongleMysql->parseGroupConcat("SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM tags");
    }

    /**
     * @Subject
     */
    public function benchParseGroupConcatSqlite()
    {
        $this->dongleSqlite->parseGroupConcat("SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM tags");
    }

    /**
     * @Subject
     */
    public function benchParseGroupConcatPgsql()
    {
        $this->donglePgsql->parseGroupConcat("SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM tags");
    }

    /**
     * @Subject
     */
    public function benchParseIfNullMysql()
    {
        $this->dongleMysql->parseIfNull("SELECT IFNULL(nickname, username) FROM users");
    }

    /**
     * @Subject
     */
    public function benchParseIfNullPgsql()
    {
        $this->donglePgsql->parseIfNull("SELECT IFNULL(nickname, username) FROM users");
    }

    /**
     * @Subject
     */
    public function benchParseBooleanExpressionMysql()
    {
        $this->dongleMysql->parseBooleanExpression("SELECT * FROM users WHERE is_active = true AND is_deleted = false");
    }

    /**
     * @Subject
     */
    public function benchParseBooleanExpressionSqlite()
    {
        $this->dongleSqlite->parseBooleanExpression("SELECT * FROM users WHERE is_active = true AND is_deleted = false");
    }

    /**
     * @Subject
     */
    public function benchParseFullQuerySqlite()
    {
        $this->dongleSqlite->parse("SELECT CONCAT(first_name, ' ', last_name), IFNULL(nickname, 'N/A') FROM users WHERE is_active = true");
    }

    /**
     * @Subject
     */
    public function benchParseFullQueryPgsql()
    {
        $this->donglePgsql->parse("SELECT CONCAT(first_name, ' ', last_name), IFNULL(nickname, 'N/A') FROM users WHERE is_active = true");
    }
}
