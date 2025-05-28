<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Http\Resources\TableResource;
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
            'denah_meja' => null,
        ]);

        return response()->json(['message' => 'Kursi berhasil ditambahkan', 'data' => $kursi]);
    }




    public function uploadDenah(Request $request, $id)
    {
        $request->validate([
            'denah_meja' => 'required|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        $path = $request->file('denah_meja')->store("denah/$id", 'public');

        // Update semua kursi restoran dengan denah ini
        Table::where('restoran_id', $id)->update(['denah_meja' => $path]);

        return response()->json(['message' => 'Denah meja berhasil diunggah', 'path' => $path]);
    }




    public function destroy($id)
    {
        $kursi = Table::findOrFail($id);
        $kursi->delete();

        return response()->json(['message' => 'Kursi berhasil dihapus']);
    }

}
