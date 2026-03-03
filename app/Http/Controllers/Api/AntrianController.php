<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Antrian;
use App\Models\Layanan;
use Illuminate\Support\Facades\DB;

class AntrianController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'layanan_id' => 'required|exists:layanans,id'
        ]);

        $layanan = Layanan::findOrFail($request->layanan_id);

        $antrian = DB::transaction(function () use ($layanan) {

            $today = now()->toDateString();

            $last = Antrian::where('layanan_id', $layanan->id)
                ->whereDate('tanggal', $today)
                ->lockForUpdate()
                ->orderByDesc('nomor_urutan')
                ->first();

            $next = $last ? $last->nomor_urutan + 1 : 1;

            $formatted = $layanan->kode . '-' . str_pad($next, 3, '0', STR_PAD_LEFT);

            return Antrian::create([
                'layanan_id' => $layanan->id,
                'tanggal' => $today,
                'nomor_urutan' => $next,
                'nomor_antrian' => $formatted,
                'status' => 'menunggu',
            ]);
        });

        return response()->json([
            'message' => 'Nomor antrian berhasil dibuat',
            'data' => $antrian
        ], 201);
    }

    public function callNext(Request $request)
    {
        $request->validate([
            'layanan_id' => 'required|exists:layanans,id'
        ]);

        $antrian = DB::transaction(function () use ($request) {

            $today = now()->toDateString();

            $nextQueue = Antrian::where('layanan_id', $request->layanan_id)
                ->whereDate('tanggal', $today)
                ->where('status', 'menunggu')
                ->lockForUpdate()
                ->orderBy('nomor_urutan')
                ->first();

            if (!$nextQueue) {
                return null;
            }

            $nextQueue->update([
                'status' => 'dipanggil',
                'dipanggil_pada' => now(),
            ]);

            return $nextQueue;
        });

        if (!$antrian) {
            return response()->json([
                'message' => 'Tidak ada antrian menunggu'
            ], 404);
        }

        return response()->json([
            'message' => 'Antrian dipanggil',
            'data' => $antrian
        ]);
    }

    public function nowServing(Layanan $layanan)
    {
        $today = now()->toDateString();

        $current = Antrian::where('layanan_id', $layanan->id)
            ->whereDate('tanggal', $today)
            ->where('status', 'dipanggil')
            ->latest('dipanggil_pada')
            ->first();

        if (!$current) {
            return response()->json([
                'message' => 'Belum ada antrian dipanggil',
                'data' => null
            ]);
        }

        return response()->json([
            'message' => 'Antrian sedang dipanggil',
            'data' => $current
        ]);
    }

    public function skip(Antrian $antrian)
    {
        if ($antrian->status !== 'dipanggil') {
            return response()->json([
                'message' => 'Hanya antrian dipanggil yang bisa dilewati'
            ], 400);
        }

        $antrian->update([
            'status' => 'dilewati',
            'selesai_pada' => now(),
        ]);

        return response()->json([
            'message' => 'Antrian dilewati',
            'data' => $antrian
        ]);
    }

    public function finish(Antrian $antrian)
    {
        if ($antrian->status !== 'dipanggil') {
            return response()->json([
                'message' => 'Hanya antrian dipanggil yang bisa diselesaikan'
            ], 400);
        }

        $antrian->update([
            'status' => 'selesai',
            'selesai_pada' => now(),
        ]);

        return response()->json([
            'message' => 'Antrian selesai',
            'data' => $antrian
        ]);
    }

    public function displayNowServing()
    {
        $today = now()->toDateString();

        $layanans = Layanan::where('is_active', true)->get();

        $result = $layanans->map(function ($layanan) use ($today) {

            $current = Antrian::where('layanan_id', $layanan->id)
                ->whereDate('tanggal', $today)
                ->where('status', 'dipanggil')
                ->latest('dipanggil_pada')
                ->first();

            return [
                'layanan_id' => $layanan->id,
                'nama_layanan' => $layanan->nama,
                'kode' => $layanan->kode,
                'nomor_antrian' => $current ? $current->nomor_antrian : null,
            ];
        });

        return response()->json($result);
    }

    public function waitingList(Layanan $layanan)
    {
        $today = now()->toDateString();

        $waiting = Antrian::where('layanan_id', $layanan->id)
            ->whereDate('tanggal', $today)
            ->where('status', 'menunggu')
            ->orderBy('nomor_urutan')
            ->get([
                'id',
                'nomor_antrian',
                'nomor_urutan',
                'created_at'
            ]);

        return response()->json([
            'layanan' => $layanan->nama,
            'total_menunggu' => $waiting->count(),
            'data' => $waiting
        ]);
    }
}
