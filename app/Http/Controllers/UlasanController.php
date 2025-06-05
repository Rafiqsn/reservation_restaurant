<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Reservation;
use App\Models\Ulasan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UlasanController extends Controller
{

    public function store(Request $request)
    {
            $request->validate([
                'reservasi_id' => 'required|exists:reservasi,id',
                'rating' => 'required|integer|min:1|max:5',
                'komentar' => 'nullable|string',
            ]);

            try {
                $user = $request->user();
                if (!$user) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'User belum login',
                        'error' => 'Unauthorized',
                    ], 401);
                }

                $reservasi = Reservation::findOrFail($request->reservasi_id);

                if ($reservasi->pengguna_id !== $user->id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Reservasi tidak ditemukan untuk pengguna ini',
                        'error' => 'Forbidden',
                    ], 403);
                }

                // Pastikan waktu reservasi sudah lewat
                $waktuReservasi = Carbon::parse($reservasi->tanggal . ' ' . $reservasi->waktu);
                if (now()->lt($waktuReservasi)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Belum bisa memberi ulasan karena waktu reservasi belum lewat',
                        'error' => 'Forbidden',
                    ], 403);
                }

                // Cegah duplikat ulasan
                if ($reservasi->ulasan) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ulasan sudah diberikan untuk reservasi ini',
                        'error' => 'Conflict',
                    ], 409);
                }

                $ulasan = Ulasan::create([
                    'id' => Str::uuid(),
                    'reservasi_id' => $reservasi->id,
                    'restoran_id' => $reservasi->restoran_id,
                    'pengguna_id' => $reservasi->pengguna_id,
                    'rating' => $request->rating,
                    'komentar' => $request->komentar,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Ulasan berhasil dibuat',
                    'data' => $ulasan,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal membuat ulasan',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

            public function lihatUlasanRestoran(Request $request)
    {
         $user = $request->user();

        try {
            // Ambil restoran milik user yang login, bisa pakai firstOrFail kalau cuma satu
            $restoran = $user->restoran()->with(['ulasan.pengguna'])->firstOrFail();

            $ulasanList = $restoran->ulasan->map(function ($ulasan) {
                return [
                    'id' => $ulasan->id,
                    'rating' => $ulasan->rating,
                    'komentar' => $ulasan->komentar,
                    'nama_pengulas' => optional($ulasan->pengguna)->nama ?? 'Anonim',
                    'tanggal' => optional($ulasan->created_at)->format('Y-m-d'),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'restoran' => $restoran->nama,
                    'jumlah_ulasan' => $ulasanList->count(),
                    'rata_rata_rating' => round($restoran->ulasan->avg('rating'), 1),
                    'ulasan' => $ulasanList,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil ulasan restoran: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil ulasan restoran.',
            ], 500);
        }
    }

}
