<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CertificateBatch extends Model
{
    protected $fillable = [
        'batch_id',
        'event_name',
        'is_zipped',
        'zip_path',
        'total_jobs',
        'completed_jobs',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the created_at attribute with proper timezone.
     */
    public function getCreatedAtAttribute($value)
    {
        return $this->asDateTime($value)->setTimezone(config('app.timezone'));
    }

    /**
     * Get the updated_at attribute with proper timezone.
     */
    public function getUpdatedAtAttribute($value)
    {
        return $this->asDateTime($value)->setTimezone(config('app.timezone'));
    }
}
