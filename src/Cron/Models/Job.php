<?php namespace October\Rain\Cron\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cron Job Model
 *
 * @package october\cron
 * @author Alexey Bobkov, Samuel Georges
 */
class Job extends Model
{
    public $table = 'cron_queue';

    const STATUS_OPEN     = 0;
    const STATUS_WAITING  = 1;
    const STATUS_STARTED  = 2;
    const STATUS_FINISHED = 3;

    public function setStatus($status)
    {
        $this->status = $status;
        $this->save();
    }

    public function scopeIsAvailable($query)
    {
        return $query
            ->where('status', static::STATUS_OPEN)
            ->orWhere('status', static::STATUS_FINISHED)
        ;
    }
}
