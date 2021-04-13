<?php namespace October\Rain\Auth\Models;

use October\Rain\Database\Model;

/**
 * Group model
 */
class Group extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table associated with the model
     */
    protected $table = 'groups';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|between:4,16|unique:groups',
    ];

    /**
     * @var array belongsToMany relationship
     */
    public $belongsToMany = [
        'users' => [User::class, 'table' => 'users_groups']
    ];

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = [];

    /**
     * delete the group
     * @return bool
     */
    public function delete()
    {
        $this->users()->detach();
        return parent::delete();
    }
}
