<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

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
}
