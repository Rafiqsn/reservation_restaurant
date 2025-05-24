<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Http\Resources\TableResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class KursiController extends Controller{


    public function index(Request $request)
    {
        $restoranId = $request->user()->restoran->id;

        $kursi = Table::where('restoran_id', $restoranId)->get();

        return response()->json($kursi);
    }

        public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->restoran) {
            return response()->json(['message' => 'Restoran tidak ditemukan untuk user ini.'], 404);
        }

        $restoranId = $user->restoran->id;

        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $kursi = Table::create([
            'id' => Str::uuid(),
            'nomor_kursi' => $request->nomor_kursi,
            'restoran_id' => $restoranId,
            'kapasitas' => $request->kapasitas,
            'posisi' => $request->posisi,
            'status' => $request->status,
            'denah_meja' => null
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
