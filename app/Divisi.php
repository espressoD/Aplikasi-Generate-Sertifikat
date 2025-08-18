<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    protected $table = 'divisis';
    
    protected $fillable = [
        'kode_divisi',
        'inisial_unit'
    ];
    
    /**
     * Relationship dengan karyawan
     * Satu divisi memiliki banyak karyawan
     */
    public function karyawans()
    {
        return $this->hasMany(Karyawan::class, 'kode_divisi', 'kode_divisi');
    }
}
