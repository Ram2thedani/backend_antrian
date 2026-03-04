<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loket extends Model
{
    protected $fillable = ['layanan_id', 'nama', 'is_active'];

    public function layanan()
    {
        return $this->belongsTo(Layanan::class);
    }

    public function antrians()
    {
        return $this->hasMany(Antrian::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
