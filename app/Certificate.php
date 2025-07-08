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
    ];
}
