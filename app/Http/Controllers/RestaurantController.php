<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Resources\RestaurantResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends Controller
{
    public function dashboard(Request $request)
{
    $user = $request->user();
    $restoran = $user->restoran;

    if (!$restoran) {
        return response()->json(['message' => 'Restoran tidak ditemukan'], 404);
    }

    // Ambil total pelanggan unik
    $totalPelanggan = $restoran->reservasi()->distinct('pengguna_id')->count('pengguna_id');

    // Total semua reservasi
    $totalReservasi = $restoran->reservasi()->count();

    // Total dibatalkan
    $totalDibatalkan = $restoran->reservasi()->where('status', 'dibatalkan')->count();

    // Ambil status restoran dan jam operasional
    $status = $restoran->status; // 'buka' atau 'tutup'
    $jamOperasional = [
        'dari' => $restoran->jam_buka,
        'sampai' => $restoran->jam_tutup,
    ];

    // Ambil 4 reservasi terbaru
    $reservasiTerbaru = $restoran->reservasi()
        ->latest()
        ->take(4)
        ->with(['pengguna', 'kursi']) // pastikan relasi ada di model
        ->get()
        ->map(function ($r) {
            return [
                'nama' => $r->user->name,
                'kapasitas' => $r->kapasitas,
                'waktu' => $r->jam_mulai . '-' . $r->jam_selesai,
                'nomor_kursi' => $r->kursi->nomor_kursi,
            ];
        });

    return response()->json([
        'statistik' => [
            'total_pelanggan' => $totalPelanggan,
            'total_reservasi' => $totalReservasi,
            'total_dibatalkan' => $totalDibatalkan,
            'status' => ucfirst($status),
        ],
        'jam_operasional' => $jamOperasional,
        'reservasi_terbaru' => $reservasiTerbaru,
    ]);
}


    public function index()
    {
        $restaurants = Restaurant::with(['owner', 'tables', 'menus', 'jamOperasional'])->get();
        return RestaurantResource::collection($restaurants);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pemilik_id' => 'required|exists:pengguna,id',
            'nama' => 'required|string|max:255',
            'lokasi' => 'required|string',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:buka,tutup',
            'kontak' => 'required|string|max:20',
        ]);

        $restaurant = Restaurant::create([
            'id' => Str::uuid(),
            ...$validated
        ]);

        return new RestaurantResource($restaurant->load(['owner', 'tables', 'menus', 'jamOperasional']));
    }

    public function show($id)
    {
        try {
            $restaurant = Restaurant::with(['owner', 'tables', 'menus', 'jamOperasional'])->findOrFail($id);
            return new RestaurantResource($restaurant);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Restaurant not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'sometimes|string|max:255',
            'lokasi' => 'sometimes|string',
            'deskripsi' => 'nullable|string',
            'status' => 'sometimes|in:buka,tutup',
            'kontak' => 'sometimes|string|max:20',
        ]);

        $restaurant->update($validated);

        return new RestaurantResource($restaurant->load(['owner', 'tables', 'menus', 'jamOperasional']));
    }

    public function destroy($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->delete();

        return response()->json(['message' => 'Restoran berhasil dihapus']);
    }


        public function showuser(Request $request)
    {
        $user = $request->user();

        $data = [
            'nama' => $user->nama,
            'email' => $user->email,
            'no_hp' => $user->no_hp,
            'nama_restoran' => $user->restoran->nama ?? null,
            'lokasi' => $user->restoran->lokasi ?? null,
            'deskripsi' => $user->restoran->deskripsi ?? null,
            'surat_halal' => $user->restoran->surat_halal ?? null,
            'nomor_induk_berusaha' => $user->restoran->nomor_induk_berusaha ?? null,
        ];

        return response()->json($data);
    }



        public function updateuser(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'no_hp' => 'nullable|string|max:20',
            'nama_restoran' => 'sometimes|required|string|max:255',
            'lokasi' => 'nullable|string',
            'deskripsi' => 'nullable|string',
            'surat_halal' => 'nullable|image|mimes:jpeg,png,jpg|max:255',
            'nib' => 'nullable|string|max:255',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update user
        $user->nama = $request->nama;
        $user->email = $request->email;
        $user->no_hp = $request->no_hp;

        // Upload foto jika ada
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/foto'), $filename);

            $user->foto = 'uploads/foto/' . $filename;
        }

        $user->save();

        // Update restoran
        if ($user->restoran) {
            $user->restoran->nama = $request->nama_restoran;
            $user->restoran->lokasi = $request->lokasi;
            $user->restoran->deskripsi = $request->deskripsi;
            $user->restoran->surat_halal = $request->surat_halal;
            $user->restoran->nib = $request->nib;
            $user->restoran->save();
        }

        return response()->json([
            'message' => 'Pengaturan berhasil diperbarui',
            'user' => $user,
            'restoran' => $user->restoran
        ]);
    }

}
