<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use Illuminate\Http\Request;

class LayananController extends Controller
{
    public function index()
    {
        $layanans = Layanan::where('is_active', true)
            ->select('id', 'nama', 'kode')
            ->get();

        return response()->json($layanans);
    }

    
}
