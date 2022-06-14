<?php

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 * @Iterations(5)
 */
class GeneralBench
{
    /**
     * init
     */
    public function init()
    {

    }

    /**
     * @Subject
     */
    public function benchA()
    {
        (new \October\Rain\Parse\Markdown)->parse('**Hello**');
    }

    /**
     * @Subject
     */
    public function benchB()
    {
        \Str::markdown('**Hello**');
    }
}
