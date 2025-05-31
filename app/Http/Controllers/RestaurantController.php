<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\JamOperasional;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Resources\RestaurantResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends Controller
{
    public function dashboard(Request $request)
    {
        $restoran = Auth::user()->restoran;

        if (!$restoran) {
            return response()->json(['message' => 'Restoran tidak ditemukan'], 404);
        }

        $totalPelanggan = User::whereHas('reservations', function ($query) use ($restoran) {
            $query->where('restoran_id', $restoran->id);
        })->distinct()->count();

        $totalReservasi = $restoran->reservations()->count();
        $totalDibatalkan = $restoran->reservations()->where('status', 'dibatalkan')->count();

        $reservasiTerbaru = $restoran->reservations()
            ->with('user', 'kursi')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($r) {
                return [
                    'nama' => $r->user->nama ?? 'N/A',
                    'jumlah_orang' => $r->jumlah_orang,
                    'waktu' => date('H:i', strtotime($r->waktu)) . '-' . date('H:i', strtotime('+1 hour', strtotime($r->waktu))),
                    'nomor_meja' => $r->kursi->nomor_kursi ?? '-',
                ];
            });

        return response()->json([
            'total_pelanggan' => $totalPelanggan,
            'total_reservasi' => $totalReservasi,
            'total_dibatalkan' => $totalDibatalkan,
            'reservasi_terbaru' => $reservasiTerbaru,
            'status' => $restoran->status, // 'buka' atau 'tutup'
            'jam_operasional' => $restoran->jamOperasional, // harus sudah punya relasi
        ]);
    }

        public function updateOperasional(Request $request, $restoranId)
    {
        $user = Auth::user();
        $restoran = $user->restoran;

        if (!$restoran || $restoran->id !== $restoranId) {
            return response()->json(['message' => 'Restoran tidak ditemukan atau bukan milik Anda'], 404);
        }

        $validated = $request->validate([
            'status' => 'nullable|boolean',
            'jam_buka' => 'nullable|date_format:H:i',
            'jam_tutup' => 'nullable|date_format:H:i',
        ]);


        $jam = jamOperasional::where('restoran_id', $restoran->id)->first();

        if (!$jam) {

            $jam = jamOperasional::create([
                'restoran_id' => $restoran->id,
                'jam_buka' => $validated['jam_buka'] ?? '08:00',
                'jam_tutup' => $validated['jam_tutup'] ?? '17:00',
            ]);
        } else {

            $jam->update([
                'jam_buka' => $validated['jam_buka'] ?? $jam->jam_buka,
                'jam_tutup' => $validated['jam_tutup'] ?? $jam->jam_tutup,
            ]);
        }

        if (isset($validated['status'])) {
            $restoran->update([
                'status' => $validated['status'],
            ]);
        }

        return response()->json(['message' => 'Jam operasional dan status berhasil diperbarui.']);
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
            'nib' => $user->restoran->nib ?? null,
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
            'surat_halal' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'nib' => 'nullable|string|max:255',
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
        $user->save();

        // Update restoran
        if ($user->restoran) {
            $restoran = $user->restoran;

            $restoran->nama = $request->nama_restoran;
            $restoran->lokasi = $request->lokasi;
            $restoran->deskripsi = $request->deskripsi;
            $restoran->nib = $request->nib;

            // Upload foto restoran jika ada
            if ($request->hasFile('foto')) {
                $file = $request->file('foto');
                $filename = time() . '_foto.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/foto'), $filename);

                $restoran->foto = 'uploads/foto/' . $filename;
            }

            // Upload surat halal jika ada
            if ($request->hasFile('surat_halal')) {
                $file = $request->file('surat_halal');
                $filename = time() . '_halal.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/surat_halal'), $filename);

                $restoran->surat_halal = 'uploads/surat_halal/' . $filename;
            }

            $restoran->save();
        }

        return response()->json([
            'message' => 'Pengaturan berhasil diperbarui',
            'user' => $user,
        ]);
    }


}
