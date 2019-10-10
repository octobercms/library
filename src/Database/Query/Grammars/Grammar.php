<?php

namespace October\Rain\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;

class Grammar extends BaseGrammar
{
    /**
     * Stores concatenated columns.
     *
     * @var array
     */
    protected $concats = [];

    /**
     * Adds a concatenated value as a SELECT column.
     *
     * @param array $parts The concatenation parts.
     * @param string $as The alias to return the entire concatenation as.
     * @return void
     */
    public function addSelectConcat(array $parts, string $as)
    {
        $this->concats[$as] = $parts;
    }

    /**
     * Compile the "select *" portion of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if (! is_null($query->aggregate)) {
            return;
        }

        $select = $query->distinct ? 'select distinct ' : 'select ';

        $sql = $select . $this->columnize($columns);

        if (count($this->concats)) {
            $sql .= $this->compileConcats();
        }

        return $sql;
    }

    /**
     * Compiles the concatenated columns and adds them to the "select" portion of the query.
     *
     * @return string
     */
    protected function compileConcats()
    {
        $columns = [];

        foreach ($this->concats as $as => $parts) {
            $columns[] = $this->compileConcat($parts, $as);
        }

        return ', ' . implode(', ', $columns);
    }

    /**
     * Compiles a single CONCAT value.
     *
     * @param array $parts The concatenation parts.
     * @param string $as The alias to return the entire concatenation as.
     * @return string
     */
    protected function compileConcat(array $parts, string $as)
    {
        $compileParts = [];

        foreach ($parts as $part) {
            if (preg_match('/^[a-z_@#][a-z0-9@$#_]*$/', $part)) {
                $compileParts[] = $this->wrap($part);
            } else {
                $compileParts[] = $this->wrap(new Expression('"' . $part . '"'));
            }
        }

        return implode(' || ', $compileParts) . ' AS ' . $this->wrap($as);
    }
}
