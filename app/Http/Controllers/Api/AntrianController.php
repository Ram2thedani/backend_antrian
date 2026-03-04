<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Antrian;
use App\Models\Layanan;
use App\Models\Loket;
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
    /**
     * Call next queue.
     *
     * @group Antrian
     * @authenticated
     *
     * Mengambil antrian berikutnya untuk loket yang sedang login.
     *
     * @response 200 {
     *   "message": "Antrian dipanggil",
     *   "data": {
     *     "id": 4,
     *     "nomor_antrian": "INF-001",
     *     "status": "dipanggil",
     *     "dipanggil_pada": "2026-03-04 12:23:29"
     *   }
     * }
     *
     * @response 404 {
     *   "message": "Tidak ada antrian menunggu"
     * }
     */
    public function callNext(Request $request)
    {
        $user = $request->user();

        if (!$user->loket_id) {
            return response()->json([
                'message' => 'User belum terhubung ke loket'
            ], 403);
        }

        $loket = $user->loket;

        $antrian = DB::transaction(function () use ($loket) {

            $today = now()->toDateString();

            // Close previous queue for THIS loket
            Antrian::where('loket_id', $loket->id)
                ->whereDate('tanggal', $today)
                ->where('status', 'dipanggil')
                ->update([
                    'status' => 'selesai',
                    'selesai_pada' => now(),
                ]);

            // Get next waiting queue
            $nextQueue = Antrian::where('layanan_id', $loket->layanan_id)
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
                'loket_id' => $loket->id,
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
    /**
     * Menampilkan nomor antrian yang sedang dilayani di 1 layanan.
     *
     * @group Antrian
     */
    public function nowServing($layanan_id)
    {
        $today = now()->toDateString();

        $current = Antrian::with('loket')
            ->where('layanan_id', $layanan_id)
            ->whereDate('tanggal', $today)
            ->where('status', 'dipanggil')
            ->get();

        return response()->json([
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
