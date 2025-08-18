<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $table = 'karyawans';
    
    protected $fillable = [
        'nama',
        'npk', 
        'kode_divisi'
    ];
    
    /**
     * Relationship dengan divisi
     * Karyawan belongs to satu divisi
     */
    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'kode_divisi', 'kode_divisi');
    }
    
    /**
     * Scope untuk search berdasarkan nama atau divisi
     */
    public function scopeSearch($query, $term)
    {
        if ($term) {
            return $query->where(function($q) use ($term) {
                $q->where('nama', 'like', '%' . $term . '%')
                  ->orWhere('kode_divisi', 'like', '%' . $term . '%')
                  ->orWhere('npk', 'like', '%' . $term . '%')
                  ->orWhereHas('divisi', function($subQuery) use ($term) {
                      $subQuery->where('inisial_unit', 'like', '%' . $term . '%');
                  });
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
            return $query->where('kode_divisi', $divisi);
        }
        return $query;
    }
}
