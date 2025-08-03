<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Certificate extends Model
{
    protected $fillable = [
        'recipient_name',
        'event_name',
        'event_date',
        'certificate_number',
        'batch_id',
        'pdf_path',
        'participant_data',
        'template_data',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'event_date',
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
