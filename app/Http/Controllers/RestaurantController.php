<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\RestoranFoto;
use App\Models\JamOperasional;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Resources\RestaurantResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RestaurantController extends Controller
{
    public function dashboard(Request $request)
    {
        try {
            $restoran = Auth::user()->restoran;

            if (!$restoran) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Restoran tidak ditemukan.',
                ], 404);
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
                'status' => 'success',
                'message' => 'Data dashboard berhasil diambil.',
                'data' => [
                    'total_pelanggan' => $totalPelanggan,
                    'total_reservasi' => $totalReservasi,
                    'total_dibatalkan' => $totalDibatalkan,
                    'reservasi_terbaru' => $reservasiTerbaru,
                    'status' => $restoran->status,
                    'jam_operasional' => $restoran->jamOperasional,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data dashboard.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        public function updateOperasional(Request $request)
    {
        try {
            $user = Auth::user();
            $restoran = $user->restoran;

            if (!$restoran) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Restoran tidak ditemukan atau belum terhubung ke akun Anda.',
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'nullable|boolean',
                'jam_buka' => 'nullable|date_format:H:i',
                'jam_tutup' => 'nullable|date_format:H:i',
            ]);

            $jam = jamOperasional::firstOrNew(['restoran_id' => $restoran->id]);

            $jam->jam_buka = $validated['jam_buka'] ?? $jam->jam_buka ?? '08:00';
            $jam->jam_tutup = $validated['jam_tutup'] ?? $jam->jam_tutup ?? '17:00';
            $jam->save();

            if (array_key_exists('status', $validated)) {
                $restoran->update([
                    'status' => $validated['status'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Jam operasional dan status berhasil diperbarui.',
                'data' => [
                    'status' => $restoran->status,
                    'jam_operasional' => $jam,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update jam operasional error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui jam operasional.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
        try {
            $user = $request->user();
            $resto = $user->restoran;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_hp' => $user->no_hp,
                    'restoran' => $resto ? new RestaurantResource($resto->load(['fotoTambahan', 'owner', 'jamOperasional', 'tables', 'menus', 'ulasan'])) : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan data user: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data user.',
            ], 500);
        }
    }



        public function updateuser(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'nama' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('pengguna')->ignore($user->id),
                ],
                'no_hp' => 'nullable|string|max:20',
                'nama_restoran' => 'sometimes|required|string|max:255',
                'lokasi' => 'nullable|string',
                'deskripsi' => 'nullable|string',
                'surat_halal' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'foto' => 'nullable|array',
                'foto.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
                'nib' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $user->update([
                'nama' => $request->nama ?? $user->nama,
                'email' => $request->email ?? $user->email,
                'no_hp' => $request->no_hp ?? $user->no_hp,
            ]);

            $restoran = $user->restoran;
            if (!$restoran) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data restoran tidak ditemukan untuk user ini',
                    'data' => null,
                ], 404);
            }

            // Handle surat halal
            if ($request->hasFile('surat_halal')) {
                if ($restoran->surat_halal && file_exists(public_path('surat_halal/' . $restoran->surat_halal))) {
                    unlink(public_path('surat_halal/' . $restoran->surat_halal));
                }

                $file = $request->file('surat_halal');
                $filename = 'surat_halal_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('surat_halal'), $filename);
                $restoran->surat_halal = $filename;
            }

            // Handle foto array
            if ($request->hasFile('foto')) {
                // Hapus foto utama
                if ($restoran->foto && file_exists(public_path('foto/' . $restoran->foto))) {
                    unlink(public_path('foto/' . $restoran->foto));
                }

                // Hapus foto tambahan
                foreach ($restoran->fotoTambahan as $foto) {
                    if ($foto->nama_file && file_exists(public_path('foto/' . $foto->nama_file))) {
                        unlink(public_path('foto/' . $foto->nama_file));
                    }
                    $foto->delete();
                }

                $fotoUtama = null;
                foreach ($request->file('foto') as $index => $file) {
                    $filename = 'foto_' . time() . '_' . Str::random(5) . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('foto'), $filename);

                    if ($index === 0) {
                        $fotoUtama = $filename;
                        $restoran->foto = $fotoUtama;
                    }

                    RestoranFoto::create([
                        'id' => Str::uuid(),
                        'restoran_id' => $restoran->id,
                        'nama_file' => $filename,
                    ]);
                }
            }

            $restoran->update([
                'nama' => $request->nama_restoran ?? $restoran->nama,
                'lokasi' => $request->lokasi ?? $restoran->lokasi,
                'deskripsi' => $request->deskripsi ?? $restoran->deskripsi,
                'nib' => $request->nib ?? $restoran->nib,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diperbarui',
                'data' => new \App\Http\Resources\RestaurantResource(
                    $restoran->load(['owner', 'tables', 'menus', 'jamOperasional', 'fotoTambahan'])
                ),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal update user/restoran: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}
