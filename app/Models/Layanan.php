<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
    protected $fillable = [
        'nama',
        'kode',
        'is_active',
    ];
    public function antrians()
    {
        return $this->hasMany(Antrian::class);
    }

    public function lokets()
    {
        return $this->hasMany(Loket::class);
    }
}
