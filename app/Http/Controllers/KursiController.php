<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Http\Resources\TableResource;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class KursiController extends Controller{

        public function index(Request $request)
    {
        $user = $request->user(); // Ambil user yang sedang login

        if (!$user->restoran) {
            return response()->json(['message' => 'Restoran tidak ditemukan'], 404);
        }

        $restoranId = $user->restoran->id; // Ambil ID restoran dengan benar

        $table = Table::where('restoran_id', $restoranId)->get();

        return response()->json($table);
    }



    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->restoran) {
            return response()->json(['message' => 'Restoran tidak ditemukan'], 404);
        }

        $restoranId = $user->restoran->id;

        $request->validate([
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
            'nomor_kursi' => $request->nomor_kursi,
            'kapasitas' => $request->kapasitas,
            'posisi' => $request->posisi,
            'status' => $request->status,
        ]);

        return response()->json(['message' => 'Kursi berhasil ditambahkan', 'data' => $kursi]);
    }




    public function uploadDenah(Request $request)
    {
        $request->validate([
            'denah_meja' => 'required|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        $user = $request->user();

        if (!$user->restoran) {
            return response()->json(['message' => 'Restoran tidak ditemukan untuk user ini'], 404);
        }

        $restoran = $user->restoran;
        $file = $request->file('denah_meja');
        $filename = 'denah_' . time() . '.' . $file->getClientOriginalExtension();
        $folder = "denah/{$restoran->id}";

        // Hapus file denah lama jika ada
        if ($restoran->denah_meja) {
            $oldFile = storage_path("app/public/$folder/{$restoran->denah_meja}");
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // Simpan file denah baru
        $file->storeAs($folder, $filename, 'public');

        // Simpan nama file ke database (bukan path)
        $restoran->denah_meja = $filename;
        $restoran->save();

        // Buat URL lengkap denah
        $url = url("storage/$folder/$filename");

        return response()->json([
            'message' => 'Denah meja berhasil diunggah',
            'filename' => $filename,
            'url' => $url,
        ]);
    }





    public function destroy($id)
    {
        $kursi = Table::findOrFail($id);
        $kursi->delete();

        return response()->json(['message' => 'Kursi berhasil dihapus']);
    }

}
