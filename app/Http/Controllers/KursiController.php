<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Http\Resources\TableResource;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class KursiController extends Controller{

        public function index(Request $request)
{
    try {
        $user = $request->user();

        if (!$user->restoran) {
            return response()->json([
                'status' => 'error',
                'message' => 'Restoran tidak ditemukan.',
            ], 404);
        }

        $restoran = $user->restoran;
        $restoranId = $restoran->id;
        $tables = Table::where('restoran_id', $restoranId)->get();

        $denahMeja = $restoran->denah_meja ? [
            'nama_file' => $restoran->denah_meja,
            'url' => url("denah/{$restoran->id}/{$restoran->denah_meja}")
        ] : null;

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar kursi berhasil diambil.',
            'data' => [
                'denah_meja' => $denahMeja,
                'tables' => $tables
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Error mengambil daftar kursi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat mengambil data kursi.',
            'error' => $e->getMessage()
        ], 500);
    }
}





        public function store(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->restoran) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Restoran tidak ditemukan.',
                ], 404);
            }

            $restoranId = $user->restoran->id;

            $validated = $request->validate([
                'nomor_kursi' => [
                    'required',
                    'integer',
                    Rule::unique('kursi')->where(function ($query) use ($restoranId) {
                        return $query->where('restoran_id', $restoranId);
                    }),
                ],
                'kapasitas' => 'required|integer',
                'posisi' => 'required|in:didalam,diluar',
                'status' => 'required|in:tersedia,dipesan',
            ]);

            $kursi = Table::create([
                'id' => Str::uuid(),
                'restoran_id' => $restoranId,
                'nomor_kursi' => $validated['nomor_kursi'],
                'kapasitas' => $validated['kapasitas'],
                'posisi' => $validated['posisi'],
                'status' => $validated['status'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Kursi berhasil ditambahkan.',
                'data' => $kursi,
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error menambahkan kursi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menambahkan kursi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }




        public function uploadDenah(Request $request)
    {
        try {
            $validated = $request->validate([
                'denah_meja' => 'required|image|mimes:png,jpg,jpeg|max:2048'
            ]);

            $user = $request->user();

            if (!$user->restoran) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Restoran tidak ditemukan untuk user ini.',
                ], 404);
            }

            $restoran = $user->restoran;
            $file = $request->file('denah_meja');
            $filename = 'denah_' . time() . '.' . $file->getClientOriginalExtension();
            $folder = "denah/{$restoran->id}";
            $destinationPath = public_path($folder);

            // Buat folder jika belum ada
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Hapus denah lama jika ada
            if ($restoran->denah_meja) {
                $oldFile = public_path("$folder/{$restoran->denah_meja}");
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            // Simpan file baru ke folder public
            $file->move($destinationPath, $filename);

            // Simpan ke database
            $restoran->denah_meja = $filename;
            $restoran->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Denah meja berhasil diunggah.',
                'data' => [
                    'filename' => $filename,
                    'url' => url("$folder/$filename"),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error upload denah: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengunggah denah meja.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





        public function destroy($id)
    {
        try {
            $kursi = Table::findOrFail($id);
            $kursi->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Kursi berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error menghapus kursi: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus kursi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
