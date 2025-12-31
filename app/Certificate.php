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
        // Project system fields
        'project_id',
        'canvas_state',
        'canvas_pages', // 🆕 MULTI-PAGE: Array of page states
        'is_edited',
        'edit_history',
        'page_order',
        'pdf_generated_at',
    ];

    protected $casts = [
        'canvas_state' => 'array',
        'canvas_pages' => 'array', // 🔧 MULTI-PAGE: Auto-decode JSON
        'is_edited' => 'boolean',
        'pdf_generated_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'event_date',
        'pdf_generated_at',
    ];

    /**
     * Get the project that owns this certificate
     */
    public function project()
    {
        return $this->belongsTo(CertificateProject::class, 'project_id');
    }

    /**
     * Get the batch that owns this certificate
     */
    public function batch()
    {
        return $this->belongsTo(CertificateBatch::class, 'batch_id');
    }

    /**
     * Mark this certificate as edited
     */
    public function markAsEdited($userName = 'System')
    {
        $this->is_edited = true;
        $history = $this->edit_history ? json_decode($this->edit_history, true) : [];
        $history[] = [
            'edited_at' => now()->toDateTimeString(),
            'edited_by' => $userName,
        ];
        $this->edit_history = json_encode($history);
        $this->save();

        // Update project edited count
        if ($this->project) {
            $this->project->updateEditedCount();
        }
    }

    /**
     * Save canvas state JSON
     */
    public function saveCanvasState($canvasJson)
    {
        $this->canvas_state = is_array($canvasJson) ? $canvasJson : json_decode($canvasJson, true);
        $this->save();
    }

    /**
     * Reset certificate to original canvas state
     */
    public function resetToOriginal()
    {
        $this->is_edited = false;
        $this->edit_history = null;
        // Keep original canvas_state from generation
        $this->save();

        // Update project edited count
        if ($this->project) {
            $this->project->updateEditedCount();
        }
    }

    /**
     * Check if certificate is part of an editable project
     */
    public function isEditable()
    {
        return $this->project && $this->project->isEditable();
    }

    /**
     * Get canvas state as JSON string
     */
    public function getCanvasJson()
    {
        return json_encode($this->canvas_state);
    }

    /**
     * Check if PDF has been generated
     */
    public function isPdfGenerated()
    {
        return !is_null($this->pdf_generated_at) && !is_null($this->pdf_path);
    }

    /**
     * Scope: Only certificates in projects
     */
    public function scopeInProject($query)
    {
        return $query->whereNotNull('project_id');
    }

    /**
     * Scope: Only standalone certificates (not in projects)
     */
    public function scopeStandalone($query)
    {
        return $query->whereNull('project_id');
    }

    /**
     * Scope: Only edited certificates
     */
    public function scopeEdited($query)
    {
        return $query->where('is_edited', true);
    }

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
