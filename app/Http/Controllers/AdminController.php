<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\RestoranFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use App\Http\Resources\RestaurantResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    // Dashboard
    public function dashboard()
    {
        try {
            $totalPelanggan = User::where('peran', 'pemesan')->count();
            $totalRestoran = Restaurant::count();

            $restoranBaru = User::where('peran', 'penyedia')
                ->with('restoran:id,pemilik_id,nama')
                ->latest()
                ->take(5)
                ->get(['id', 'email']);

            $userBaru = User::where('peran', 'pemesan')
                ->latest()
                ->take(5)
                ->get(['nama', 'email']);

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'total_pelanggan' => $totalPelanggan,
                    'total_restoran' => $totalRestoran,
                    'restoran_baru' => $restoranBaru,
                    'user_baru' => $userBaru,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'data' => null,
            ], 500);
        }
    }

    // Manajemen user - list penyedia
    public function index()
    {
        try {
            $users = User::where('peran', 'penyedia')->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Users retrieved successfully',
                'data' => UserResource::collection($users),
            ]);
        } catch (\Exception $e) {
            Log::error('User index error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve users',
                'data' => null,
            ], 500);
        }
    }

    // POST /admin/users - create penyedia dan resto
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pengguna,email',
            'kata_sandi' => 'required|string|min:6',
            'no_hp' => 'required|string|max:20',

            'nama_resto' => 'required|string|max:255',
            'lokasi' => 'required|string',
            'deskripsi' => 'nullable|string',
            'nib' => 'required|string|max:20',
            'status' => 'required|in:buka,tutup',
            'kontak' => 'required|string|max:20',
            'surat_halal' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'foto' => 'nullable|array',
            'foto.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'id' => Str::uuid(),
                'nama' => $validated['nama'],
                'email' => $validated['email'],
                'kata_sandi' => Hash::make($validated['kata_sandi']),
                'no_hp' => $validated['no_hp'],
                'peran' => 'penyedia',
            ]);

            $suratHalalFilename = null;
            if ($request->hasFile('surat_halal')) {
                $file = $request->file('surat_halal');
                $suratHalalFilename = 'surat_halal_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('surat_halal'), $suratHalalFilename);
            }

            $fotoFiles = $request->file('foto');
            $fotoUtama = null;
            $fotoCollection = [];

            if ($fotoFiles && is_array($fotoFiles)) {
                foreach ($fotoFiles as $index => $file) {
                    $filename = 'foto_' . time() . '_' . Str::random(5) . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('foto'), $filename);

                    if ($index === 0) {
                        $fotoUtama = $filename;
                    }

                    $fotoCollection[] = [
                        'id' => Str::uuid(),
                        'nama_file' => $filename,
                    ];
                }
            }

            $restaurant = Restaurant::create([
                'id' => Str::uuid(),
                'pemilik_id' => $user->id,
                'nama' => $validated['nama_resto'],
                'lokasi' => $validated['lokasi'],
                'deskripsi' => $validated['deskripsi'] ?? null,
                'status' => $validated['status'],
                'kontak' => $validated['kontak'],
                'foto' => $fotoUtama,
                'nib' => $validated['nib'],
                'surat_halal' => $suratHalalFilename,
            ]);

            foreach ($fotoCollection as $fotoData) {
                RestoranFoto::create([
                    'id' => $fotoData['id'],
                    'restoran_id' => $restaurant->id,
                    'nama_file' => $fotoData['nama_file'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'User and restaurant created successfully',
                'data' => new RestaurantResource(
                    $restaurant->load(['owner', 'tables', 'menus', 'jamOperasional', 'fotoTambahan'])
                ),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Gagal menyimpan data restoran', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data.',
                'error' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    // GET /admin/users/{id} - detail user dan restoran
    public function show($id)
    {
        try {
            $user = User::where('peran', 'penyedia')
                ->with('restoran')
                ->findOrFail($id);

            $resto = $user->restoran;

            return response()->json([
                'status' => 'success',
                'message' => 'User retrieved successfully',
                'data' => [
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_hp' => $user->no_hp,

                    'nama_resto' => $resto?->nama,
                    'lokasi' => $resto?->lokasi,
                    'status' => $resto?->status,
                    'kontak' => $resto?->kontak,
                    'foto' => $resto?->foto ? url('storage/' . $resto->foto) : null,
                    'nib' => $resto?->nib,
                    'surat_halal' => $resto?->surat_halal ? url('storage/' . $resto->surat_halal) : null,

                    'created_at' => $user->created_at->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Show user error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
                'data' => null,
            ], 404);
        }
    }

    // PUT /admin/users/{id} - update user dan restoran
    public function update(Request $request, $id)
    {
        try {
            $user = User::where('peran', 'penyedia')->findOrFail($id);

            $validated = $request->validate([
                'nama' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'email', Rule::unique('pengguna')->ignore($user->id)],
                'no_hp' => 'sometimes|string|max:20',

                'nama_resto' => 'sometimes|string|max:255',
                'lokasi' => 'sometimes|string',
                'deskripsi' => 'nullable|string',
                'nib' => 'sometimes|string|max:20',
                'status' => 'sometimes|in:buka,tutup',
                'kontak' => 'sometimes|string|max:20',
                'surat_halal' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'foto' => 'nullable|array',
                'foto.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            DB::beginTransaction();

            $user->update([
                'nama' => $validated['nama'] ?? $user->nama,
                'email' => $validated['email'] ?? $user->email,
                'no_hp' => $validated['no_hp'] ?? $user->no_hp,
            ]);

            $resto = $user->restoran;

            if (!$resto) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Restaurant not found for this user',
                    'data' => null,
                ], 404);
            }

            // Handle surat halal
            if ($request->hasFile('surat_halal')) {
                // Hapus file lama jika ada
                if ($resto->surat_halal && file_exists(public_path('surat_halal/' . $resto->surat_halal))) {
                    unlink(public_path('surat_halal/' . $resto->surat_halal));
                }

                $file = $request->file('surat_halal');
                $suratHalalFilename = 'surat_halal_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('surat_halal'), $suratHalalFilename);
                $resto->surat_halal = $suratHalalFilename;
            }

            // Handle foto
            if ($request->hasFile('foto')) {
                // Hapus semua foto lama di RestoranFoto dan folder foto utama
                if ($resto->foto && file_exists(public_path('foto/' . $resto->foto))) {
                    unlink(public_path('foto/' . $resto->foto));
                }

                // Hapus foto tambahan lama
                foreach ($resto->fotoTambahan as $foto) {
                    if ($foto->nama_file && file_exists(public_path('foto/' . $foto->nama_file))) {
                        unlink(public_path('foto/' . $foto->nama_file));
                    }
                    $foto->delete();
                }

                $fotoFiles = $request->file('foto');
                $fotoUtama = null;
                $fotoCollection = [];

                foreach ($fotoFiles as $index => $file) {
                    $filename = 'foto_' . time() . '_' . Str::random(5) . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('foto'), $filename);

                    if ($index === 0) {
                        $fotoUtama = $filename;
                    }

                    $fotoCollection[] = [
                        'id' => Str::uuid(),
                        'nama_file' => $filename,
                    ];
                }

                $resto->foto = $fotoUtama;

                foreach ($fotoCollection as $fotoData) {
                    RestoranFoto::create([
                        'id' => $fotoData['id'],
                        'restoran_id' => $resto->id,
                        'nama_file' => $fotoData['nama_file'],
                    ]);
                }
            }

            $resto->update([
                'nama' => $validated['nama_resto'] ?? $resto->nama,
                'lokasi' => $validated['lokasi'] ?? $resto->lokasi,
                'deskripsi' => $validated['deskripsi'] ?? $resto->deskripsi,
                'nib' => $validated['nib'] ?? $resto->nib,
                'status' => $validated['status'] ?? $resto->status,
                'kontak' => $validated['kontak'] ?? $resto->kontak,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'User and restaurant updated successfully',
                'data' => new RestaurantResource(
                    $resto->load(['owner', 'tables', 'menus', 'jamOperasional', 'fotoTambahan'])
                ),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Update user error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    // DELETE /admin/users/{id} - hapus user dan restoran
    public function destroy($id)
    {
        try {
            $user = User::where('peran', 'penyedia')->findOrFail($id);

            $resto = $user->restoran;

            DB::beginTransaction();

            if ($resto) {
                // Hapus foto utama
                if ($resto->foto && file_exists(public_path('foto/' . $resto->foto))) {
                    unlink(public_path('foto/' . $resto->foto));
                }

                // Hapus surat halal
                if ($resto->surat_halal && file_exists(public_path('surat_halal/' . $resto->surat_halal))) {
                    unlink(public_path('surat_halal/' . $resto->surat_halal));
                }

                // Hapus foto tambahan
                foreach ($resto->fotoTambahan as $foto) {
                    if ($foto->nama_file && file_exists(public_path('foto/' . $foto->nama_file))) {
                        unlink(public_path('foto/' . $foto->nama_file));
                    }
                    $foto->delete();
                }

                $resto->delete();
            }

            $user->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'User and restaurant deleted successfully',
                'data' => null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Delete user error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function destroyfoto($id)
    {
        try {
            $foto = RestoranFoto::findOrFail($id);

            // Hapus file dari storage
            if (Storage::disk('public')->exists('foto/' . $foto->nama_file)) {
                Storage::disk('public')->delete('foto/' . $foto->nama_file);
            }

            // Hapus record dari database
            $foto->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Foto tambahan berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete photo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus foto tambahan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleRecommendation(Request $request, $id)
    {
        try {
            $resto = Restaurant::findOrFail($id);

            $validated = $request->validate([
                'is_recommended' => 'required|boolean',
            ]);

            $resto->is_recommended = $validated['is_recommended'];
            $resto->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Status rekomendasi berhasil diperbarui.',
                'data' => $resto,
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to toggle recommendation: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui status rekomendasi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function adminIndex()
    {
        try {
            $restaurants = Restaurant::select('id', 'nama', 'deskripsi', 'lokasi', 'foto', 'is_recommended')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar restoran berhasil diambil.',
                'data' => $restaurants,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch restaurants: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil daftar restoran.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function adminProfileShow(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->peran !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Profil admin berhasil diambil.',
                'data' => [
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_hp' => $user->no_hp,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to show admin profile: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil profil admin.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function adminProfileUpdate(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->peran !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            $validated = $request->validate([
                'nama' => 'sometimes|string|max:255',
                'email' => [
                    'sometimes',
                    'email',
                    Rule::unique('pengguna')->ignore($user->id),
                ],
                'no_hp' => 'sometimes|string|max:20',
                'kata_sandi' => 'nullable|string',
                'kata_sandi_baru' => 'nullable|string|min:8',
            ]);

            if (isset($validated['nama'])) $user->nama = $validated['nama'];
            if (isset($validated['email'])) $user->email = $validated['email'];
            if (isset($validated['no_hp'])) $user->no_hp = $validated['no_hp'];

            if (!empty($validated['kata_sandi_baru'])) {
                if (!Hash::check($validated['kata_sandi'], $user->kata_sandi)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Password lama salah',
                    ], 422);
                }
                $user->kata_sandi = Hash::make($validated['kata_sandi_baru']);
            }

            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Profil berhasil diperbarui',
                'data' => [
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'no_hp' => $user->no_hp,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update admin profile: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui profil admin.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
