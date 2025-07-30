<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
}
