<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany as MorphToManyBase;

class MorphToMany extends MorphToManyBase
{
    use BelongsOrMorphToMany;
}


