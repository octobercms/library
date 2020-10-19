<?php namespace October\Rain\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\MySqlGrammar as BaseMysqlGrammer;
use October\Rain\Database\Query\Grammars\Concerns\SelectConcatenations;

class MySqlGrammar extends BaseMysqlGrammer
{
    use SelectConcatenations;
}
