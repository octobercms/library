<?php namespace October\Rain\Parse\Parsedown;

use ParsedownExtra;

class Parsedown extends ParsedownExtra
{
    function setUnmarkedBlockTypes($unmarkedBlockTypes)
    {
        $this->unmarkedBlockTypes = $unmarkedBlockTypes;

        return $this;
    }
}
