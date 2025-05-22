<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Http\Resources\TableResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KursiController extends Controller{

    public function index($id)
    {
        return response()->json([
            'data' => Table::where('restoran_id', $id)->get()
        ]);
    }



    public function store(Request $request)
    {
        $request->validate([
            'restoran_id' => 'required|uuid',
            'nomor_kursi' => 'required|integer',
            'kapasitas' => 'required|integer',
            'posisi' => 'required|in:didalam,diluar',
            'status' => 'required|in:tersedia,dipesan',
        ]);

        $kursi = Table::create([
            'id' => Str::uuid(),
            'restoran_id' => $request->restoran_id,
            'nomor_kursi' => $request->nomor_kursi,
            'kapasitas' => $request->kapasitas,
            'posisi' => $request->posisi,
            'status' => $request->status,
            'denah_meja' => null // default null jika belum ada
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
