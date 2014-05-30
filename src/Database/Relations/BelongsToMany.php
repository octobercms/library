<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;
use Illuminate\Database\Eloquent\Model;

class BelongsToMany extends BelongsToManyBase
{
    use BelongsOrMorphToMany;
}
