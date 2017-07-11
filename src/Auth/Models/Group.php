<?php namespace October\Rain\Auth\Models;

use InvalidArgumentException;
use October\Rain\Database\Model;

/**
 * Group model
 */
class Group extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The table associated with the model.
     */
    protected $table = 'groups';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'name' => 'required|between:4,16|unique:groups',
    ];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'users' => [User::class, 'table' => 'users_groups']
    ];

    /**
     * @var array The attributes that aren't mass assignable.
     */
    protected $guarded = [];

    /**
     * Delete the group.
     * @return bool
     */
    public function delete()
    {
        $this->users()->detach();
        return parent::delete();
    }
}
