<?php namespace October\Rain\Database\Query\Grammars;

use Illuminate\Database\Query\Grammars\PostgresGrammar as BasePostgresGrammer;
use October\Rain\Database\Query\Grammars\Concerns\SelectConcatenations;

class PostgresGrammar extends BasePostgresGrammer
{
    use SelectConcatenations;
}
