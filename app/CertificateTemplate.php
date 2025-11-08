<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'template_data',
        'design_settings',
    ];

    /**
     * Cast design_settings to array for easier usage.
     */
    protected $casts = [
        'design_settings' => 'array',
    ];
}
