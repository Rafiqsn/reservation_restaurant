<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Reservation;
use App\Models\Ulasan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UlasanController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'reservasi_id' => 'required|exists:reservasi,id',
            'rating' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string',
        ]);

        $reservasi = Reservation::findOrFail($request->reservasi_id);

        // Pastikan waktu reservasi sudah lewat
        $waktuReservasi = Carbon::parse($reservasi->tanggal . ' ' . $reservasi->waktu);
        if (now()->lt($waktuReservasi)) {
            return response()->json(['error' => 'Belum bisa memberi ulasan.'], 403);
        }

        // Cegah duplikat ulasan
        if ($reservasi->ulasan) {
            return response()->json(['error' => 'Ulasan sudah diberikan.'], 409);
        }

        $ulasan = Ulasan::create([
            'id' => Str::uuid(),
            'reservasi_id' => $reservasi->id,
            'restoran_id' => $reservasi->restoran_id,
            'pengguna_id' => $reservasi->pengguna_id,
            'rating' => $request->rating,
            'komentar' => $request->komentar,
        ]);

        return response()->json(['status' => 'success', 'data' => $ulasan]);
    }

    public function lihatUlasanRestoran($id)
    {
        $restoran = Restaurant::with(['ulasan.pengguna'])->findOrFail($id);

        $ulasanList = $restoran->ulasan->map(function ($ulasan) {
            return [
                'id' => $ulasan->id,
                'rating' => $ulasan->rating,
                'komentar' => $ulasan->komentar,
                'nama_pengulas' => optional($ulasan->pengguna)->nama,
                'tanggal' => optional($ulasan->created_at)->format('Y-m-d'),
            ];
        });

        return response()->json([
            'restoran' => $restoran->nama,
            'jumlah_ulasan' => $ulasanList->count(),
            'rata_rata_rating' => round($restoran->ulasan->avg('rating'), 1),
            'ulasan' => $ulasanList,
        ]);
    }
}
