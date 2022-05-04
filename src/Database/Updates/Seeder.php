<?php namespace October\Rain\Database\Updates;

use Illuminate\Database\Seeder as SeederBase;

/**
 * Seeder
 */
class Seeder extends SeederBase
{
    /**
     * line writes a string as standard output.
     * @param  string  $string
     * @param  string|null  $style
     * @return void
     */
    public function line($string, $style = null)
    {
        if (!isset($this->command)) {
            return;
        }

        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->command->getOutput()->writeln($styled);
    }
}
