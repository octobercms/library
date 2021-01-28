<?php namespace October\Rain\Database\Query\Grammars\Concerns;

use \Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;

trait SelectConcatenations
{
    /**
     * Compile the "select *" portion of the query.
     *
     * This particular method will call the original compileColumns() method provided by the grammar, then append
     * the concatenated columns to the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        $select = parent::compileColumns($query, $columns);

        if (count($query->concats)) {
            $select .= $this->compileConcats($query);
        }

        return $select;
    }

    /**
     * Compiles the concatenated columns and adds them to the "select" portion of the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    protected function compileConcats(Builder $query)
    {
        $columns = [];

        foreach ($query->concats as $as => $parts) {
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
                $compileParts[] = $this->wrap(new Expression('\'' . trim($part, '\'"') . '\''));
            }
        }

        return 'concat(' . implode(', ', $compileParts) . ') as ' . $this->wrap($as);
    }
}
