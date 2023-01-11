<?php namespace October\Rain\Auth\Concerns;

use October\Rain\Auth\AuthException;

/**
 * HasThrottle
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasThrottle
{
    /**
     * createThrottleModel creates an instance of the throttle model.
     * @return Models\Throttle
     */
    public function createThrottleModel()
    {
        $class = '\\'.ltrim($this->throttleModel, '\\');
        return new $class();
    }

    /**
     * findThrottleByLogin and ip address
     *
     * @param string $loginName
     * @param string $ipAddress
     * @return Models\Throttle
     */
    public function findThrottleByLogin($loginName, $ipAddress)
    {
        $user = $this->findUserByLogin($loginName);
        if (!$user) {
            throw new AuthException('A user was not found with the given credentials.', 200);
        }

        $userId = $user->getKey();
        return $this->findThrottleByUserId($userId, $ipAddress);
    }

    /**
     * findThrottleByUserId and ip address
     *
     * @param integer $userId
     * @param string $ipAddress
     * @return Models\Throttle
     */
    public function findThrottleByUserId($userId, $ipAddress = null)
    {
        $cacheKey = md5($userId.$ipAddress);
        if (isset($this->throttle[$cacheKey])) {
            return $this->throttle[$cacheKey];
        }

        $model = $this->createThrottleModel();
        $query = $model->where('user_id', '=', $userId);

        if ($ipAddress) {
            $query->where(function ($query) use ($ipAddress) {
                $query->where('ip_address', '=', $ipAddress);
                $query->orWhere('ip_address', '=', null);
            });
        }

        if (!$throttle = $query->first()) {
            $throttle = $this->createThrottleModel();
            $throttle->user_id = $userId;
            if ($ipAddress) {
                $throttle->ip_address = $ipAddress;
            }

            $throttle->save();
        }

        return $this->throttle[$cacheKey] = $throttle;
    }

    /**
     * clearThrottleForUserId unsuspends and clears all throttles records for a user
     */
    public function clearThrottleForUserId($userId)
    {
        if (!$userId) {
            return;
        }

        $model = $this->createThrottleModel();

        $throttles = $model->where('user_id', $userId)->get();

        foreach ($throttles as $throttle) {
            $throttle->unsuspend();
        }
    }
}
