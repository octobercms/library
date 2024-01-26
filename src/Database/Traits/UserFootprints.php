<?php namespace October\Rain\Database\Traits;

use App;

/**
 * UserFootprints adds created_user_id and updated_user_id fields to a model and populates
 * them using the logged in user via the backend.auth provider.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait UserFootprints
{
    /**
     * initializeUserFootprints trait for a model.
     */
    public function initializeUserFootprints()
    {
        $this->bindEvent('model.saveInternal', function () {
            $this->updateUserFootprints();
        });

        $userModel = $this->getUserFootprintAuth()->getProvider()->getModel();

        $this->belongsTo['updated_user'] = [
            $userModel,
            'replicate' => false
        ];

        $this->belongsTo['created_user'] = [
            $userModel,
            'replicate' => false
        ];
    }

    /**
     * updateUserFootprints
     */
    public function updateUserFootprints()
    {
        $userId = $this->getUserFootprintAuth()->id();
        if (!$userId) {
            return;
        }

        $updatedColumn = $this->getUpdatedUserIdColumn();
        if ($updatedColumn !== null && !$this->isDirty($updatedColumn)) {
            $this->{$updatedColumn} = $userId;
        }

        $createdColumn = $this->getCreatedUserIdColumn();
        if (!$this->exists && $createdColumn !== null && !$this->isDirty($createdColumn)) {
            $this->{$createdColumn} = $userId;
        }
    }

    /**
     * getCreatedUserIdColumn gets the name of the "created user id" column.
     * @return string
     */
    public function getCreatedUserIdColumn()
    {
        return defined('static::CREATED_USER_ID') ? static::CREATED_USER_ID : 'created_user_id';
    }

    /**
     * getCreatedUserIdColumn gets the name of the "updated user id" column.
     * @return string
     */
    public function getUpdatedUserIdColumn()
    {
        return defined('static::UPDATED_USER_ID') ? static::UPDATED_USER_ID : 'updated_user_id';
    }

    /**
     * getUserFootprintAuth
     */
    protected function getUserFootprintAuth()
    {
        return App::make('backend.auth');
    }
}
