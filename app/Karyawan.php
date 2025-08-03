<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $table = 'karyawan';
    
    protected $fillable = [
        'nama',
        'npk_id', 
        'divisi'
    ];
    
    /**
     * Scope untuk search berdasarkan nama atau divisi
     */
    public function scopeSearch($query, $term)
    {
        if ($term) {
            return $query->where(function($q) use ($term) {
                $q->where('nama', 'like', '%' . $term . '%')
                  ->orWhere('divisi', 'like', '%' . $term . '%')
                  ->orWhere('npk_id', 'like', '%' . $term . '%');
            });
        }
        return $query;
    }
    
    /**
     * Scope untuk filter berdasarkan divisi
     */
    public function scopeDivisi($query, $divisi)
    {
        if ($divisi) {
            return $query->where('divisi', $divisi);
        }
        return $query;
    }
}
