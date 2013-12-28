<?php namespace October\Rain\Database\Updates;

use Illuminate\Database\Seeder as SeederBase;

class Seeder extends SeederBase
{

    /**
     * {@inheritDoc}
     */
    public function call($class)
    {
        $this->resolve($class)->run();

        // @todo This has been sent as a PR to Laravel:
        //       https://github.com/laravel/framework/pull/3064
        //       Remove this entire method if it is accepted
        if (isset($this->command))
        {
                $this->command->getOutput()->writeln("<info>Seeded:</info> $class");
        }
    }
}
