<?php namespace October\Rain\Database\Query\Grammars;

use October\Rain\Database\QueryBuilder;
use Illuminate\Database\Query\Grammars\PostgresGrammar as BasePostgresGrammer;
use October\Rain\Database\Query\Grammars\Concerns\SelectConcatenations;

class PostgresGrammar extends BasePostgresGrammer
{
    use SelectConcatenations;

    /**
     * Compile an "upsert" statement into SQL.
     *
     * @param  \October\Rain\Database\QueryBuilder $query
     * @param  array $values
     * @param  array $uniqueBy
     * @param  array $update
     * @return  string
     */
    public function compileUpsert(QueryBuilder $query, array $values, array $uniqueBy, array $update)
    {
        $sql = $this->compileInsert($query, $values);

        $sql .= ' on conflict (' . $this->columnize($uniqueBy) . ') do update set ';

        $columns = collect($update)->map(function ($value, $key) {
            return is_numeric($key)
                ? $this->wrap($value) . ' = ' . $this->wrapValue('excluded') . '.' . $this->wrap($value)
                : $this->wrap($key) . ' = ' . $this->parameter($value);
        })->implode(', ');

        return $sql . $columns;
    }
}
