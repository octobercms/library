<?php namespace October\Rain\Auth\Models;

use Carbon\Carbon;
use October\Rain\Auth\AuthException;
use October\Rain\Database\Model;

/**
 * Throttle model
 */
class Throttle extends Model
{
    /**
     * @var bool enabled throttling status
     */
    protected $enabled = true;

    /**
     * @var string table associated with the model
     */
    protected $table = 'throttle';

    /**
     * @var array belongsTo relation
     */
    public $belongsTo = [
        'user' => [User::class, 'key' => 'user_id']
    ];

    /**
     * @var bool timestamps indicates if the model should be timestamped
     */
    public $timestamps = false;

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = ['last_attempt_at', 'suspended_at', 'banned_at'];

    /**
     * @var int attemptLimit
     */
    protected static $attemptLimit = 5;

    /**
     * @var int suspensionTime in minutes
     */
    protected static $suspensionTime = 15;

    /**
     * getUser returns the associated user with the throttler
     * @return User
     */
    public function getUser()
    {
        return $this->user()->getResults();
    }

    /**
     * getLoginAttempts
     * @return int
     */
    public function getLoginAttempts()
    {
        if ($this->attempts > 0 && $this->last_attempt_at) {
            $this->clearLoginAttemptsIfAllowed();
        }

        return (int) $this->attempts;
    }

    /**
     * addLoginAttempt
     */
    public function addLoginAttempt()
    {
        $this->attempts++;
        $this->last_attempt_at = $this->freshTimestamp();

        if ($this->getLoginAttempts() >= static::$attemptLimit) {
            $this->suspend();
        }
        else {
            $this->save();
        }
    }

    /**
     * clearLoginAttempts
     */
    public function clearLoginAttempts()
    {
        // If our login attempts is already at zero we do not need to do anything. Additionally,
        // if we are suspended, we are not going to do anything either as clearing login attempts
        // makes us unsuspended. We need to manually call unsuspend() in order to unsuspend.
        if ($this->getLoginAttempts() === 0 || $this->is_suspended) {
            return;
        }

        $this->attempts = 0;
        $this->last_attempt_at = null;
        $this->is_suspended = false;
        $this->suspended_at = null;
        $this->save();
    }

    /**
     * suspend the user associated with the throttle
     */
    public function suspend()
    {
        if (!$this->is_suspended) {
            $this->is_suspended = true;
            $this->suspended_at = $this->freshTimestamp();
            $this->save();
        }
    }

    /**
     * unsuspend the user
     */
    public function unsuspend()
    {
        if ($this->is_suspended) {
            $this->attempts = 0;
            $this->last_attempt_at = null;
            $this->is_suspended = false;
            $this->suspended_at = null;
            $this->save();
        }
    }

    /**
     * checkSuspended checks if the user is suspended
     * @return bool
     */
    public function checkSuspended()
    {
        if ($this->is_suspended && $this->suspended_at) {
            $this->removeSuspensionIfAllowed();
            return (bool) $this->is_suspended;
        }

        return false;
    }

    /**
     * ban the user
     * @return void
     */
    public function ban()
    {
        if (!$this->is_banned) {
            $this->is_banned = true;
            $this->banned_at = $this->freshTimestamp();
            $this->save();
        }
    }

    /**
     * unban the user
     * @return void
     */
    public function unban()
    {
        if ($this->is_banned) {
            $this->is_banned = false;
            $this->banned_at = null;
            $this->save();
        }
    }

    /**
     * check user throttle status
     * @return bool
     * @throws AuthException
     */
    public function check()
    {
        if ($this->is_banned) {
            throw new AuthException(sprintf(
                'User [%s] has been banned.',
                $this->user->getLogin()
            ));
        }

        if ($this->checkSuspended()) {
            throw new AuthException(sprintf(
                'User [%s] has been suspended.',
                $this->user->getLogin()
            ));
        }

        return true;
    }

    /**
     * clearLoginAttemptsIfAllowed inspects the last attempt vs the suspension time
     * (the time in which attempts must space before the account is suspended).
     * If we can clear our attempts now, we'll do so and save.
     *
     * @return void
     */
    public function clearLoginAttemptsIfAllowed()
    {
        $lastAttempt = clone $this->last_attempt_at;

        $suspensionTime = static::$suspensionTime;
        $clearAttemptsAt = $lastAttempt->modify("+{$suspensionTime} minutes");
        $now = new Carbon;

        if ($clearAttemptsAt <= $now) {
            $this->attempts = 0;
            $this->save();
        }

        unset($lastAttempt, $clearAttemptsAt, $now);
    }

    /**
     * removeSuspensionIfAllowed inspects to see if the user can become unsuspended
     * or not, based on the suspension time provided. If so, unsuspends.
     */
    public function removeSuspensionIfAllowed()
    {
        $suspended = clone $this->suspended_at;

        $suspensionTime = static::$suspensionTime;
        $unsuspendAt = $suspended->modify("+{$suspensionTime} minutes");
        $now = new Carbon;

        if ($unsuspendAt <= $now) {
            $this->unsuspend();
        }

        unset($suspended, $unsuspendAt, $now);
    }

    /**
     * getIsSuspendedAttribute is a get mutator for the suspended property
     * @param  mixed  $suspended
     * @return bool
     */
    public function getIsSuspendedAttribute($suspended)
    {
        return (bool) $suspended;
    }

    /**
     * getIsBannedAttribute is a get mutator for the banned property
     * @param  mixed  $banned
     * @return bool
     */
    public function getIsBannedAttribute($banned)
    {
        return (bool) $banned;
    }
}
