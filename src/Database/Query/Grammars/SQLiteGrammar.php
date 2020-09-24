<?php namespace October\Rain\Database\Query\Grammars;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\SQLiteGrammar as BaseSQLiteGrammar;
use October\Rain\Database\Query\Grammars\Concerns\SelectConcatenations;

class SQLiteGrammar extends BaseSQLiteGrammar
{
    use SelectConcatenations;

    /**
     * Compiles a single CONCAT value.
     * 
     * SQLite uses slightly different concatenation syntax.
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
                $compileParts[] = $this->wrap(new Expression('\'' .trim($part, '\'"') . '\''));
            }
        }

        return implode(' || ', $compileParts) . ' as ' . $this->wrap($as);
    }
}
