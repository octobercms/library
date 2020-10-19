<?php namespace October\Rain\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\SqlServerGrammar as BaseSqlServerGrammar;
use October\Rain\Database\Query\Grammars\Concerns\SelectConcatenations;

class SqlServerGrammar extends BaseSqlServerGrammar
{
    use SelectConcatenations;
}
