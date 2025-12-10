<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CertificateProject extends Model
{
    protected $fillable = [
        'project_name',
        'event_name',
        'template_id',
        'global_settings',
        'status',
        'total_certificates',
        'edited_count',
        'zip_path',
        'finalized_at'
    ];

    protected $casts = [
        'global_settings' => 'array',
        'finalized_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get all certificates in this project
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'project_id')->orderBy('page_order');
    }

    /**
     * Get the template used for this project
     */
    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    /**
     * Update the count of edited certificates
     */
    public function updateEditedCount()
    {
        $this->edited_count = $this->certificates()
                                   ->where('is_edited', true)
                                   ->count();
        $this->save();
        
        return $this;
    }

    /**
     * Check if project is editable
     */
    public function isEditable()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if project is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Mark project as finalizing
     */
    public function startFinalizing()
    {
        $this->update(['status' => 'finalizing']);
        return $this;
    }

    /**
     * Mark project as completed
     */
    public function markCompleted($zipPath)
    {
        $this->update([
            'status' => 'completed',
            'zip_path' => $zipPath,
            'finalized_at' => now()
        ]);
        
        return $this;
    }

    /**
     * Get progress percentage for finalizing
     */
    public function getFinalizationProgress()
    {
        if ($this->status !== 'finalizing') {
            return 0;
        }

        $total = $this->total_certificates;
        $generated = $this->certificates()->whereNotNull('pdf_generated_at')->count();

        return $total > 0 ? round(($generated / $total) * 100, 2) : 0;
    }

    /**
     * Scope: Get draft projects
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: Get completed projects
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Recent projects
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
