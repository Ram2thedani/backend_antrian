<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Antrian extends Model
{
    protected $fillable = [
        'layanan_id',
        'tanggal',
        'nomor_urutan',
        'nomor_antrian',
        'status',
        'dipanggil_pada',
        'selesai_pada',
    ];
    public function layanan()
    {
        return $this->belongsTo(Layanan::class);
    }
}
