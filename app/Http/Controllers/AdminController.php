<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use App\Http\Resources\RestaurantResource;

class AdminController extends Controller
{

    //dashboard
    public function dashboard()
    {
        $totalPelanggan = User::where('peran', 'pemesan')->count();
        $totalRestoran = Restaurant::count();

        $restoranBaru = User::where('peran', 'penyedia')
            ->with('restoran:id,pemilik_id,nama') // relasi restoran
            ->latest()
            ->take(5)
            ->get(['id', 'email']);
        $userBaru = User::where('peran', 'pemesan')->latest()->take(5)->get(['nama', 'email']);

        return response()->json([
            'total_pelanggan' => $totalPelanggan,
            'total_restoran' => $totalRestoran,
            'restoran_baru' => $restoranBaru,
            'user_baru' => $userBaru,
        ]);
    }
    // manajemen user
        public function index()
    {
        $users = User::where('peran', 'penyedia')->get();
        return UserResource::collection($users);
    }


    // POST /admin/users
    public function store(Request $request)
    {
    $validated = $request->validate([
        // Validasi User
        'nama' => 'required|string|max:255',
        'email' => 'required|email|unique:pengguna,email',
        'kata_sandi' => 'required|string|min:6',
        'no_hp' => 'required|string|max:20',

        // Validasi Restoran
        'nama_resto' => 'required|string|max:255',
        'lokasi' => 'required|string',
        'deskripsi' => 'nullable|string',
        'status' => 'required|in:buka,tutup',
        'kontak' => 'required|string|max:20',
    ]);

    DB::beginTransaction();

    try {
        // Buat user (penyedia)
        $user = User::create([
            'id' => Str::uuid(),
            'nama' => $validated['nama'],
            'email' => $validated['email'],
            'kata_sandi' => Hash::make($validated['kata_sandi']),
            'no_hp' => $validated['no_hp'],
            'peran' => 'penyedia',
        ]);

        // Buat restoran yang dimiliki user ini
        $restaurant = Restaurant::create([
            'id' => Str::uuid(),
            'pemilik_id' => $user->id,
            'nama' => $validated['nama_resto'],
            'lokasi' => $validated['lokasi'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'status' => 'buka',
            'kontak' => $validated['kontak'],
        ]);

        DB::commit();

        return new RestaurantResource(
            $restaurant->load(['owner', 'tables', 'menus', 'jamOperasional'])
        );
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Gagal menyimpan data.',
            'error' => $e->getMessage()
        ], 500);
    }
    }

    // GET /admin/users/{id}
    public function show($id)
    {
        // Ambil user dan relasi restoran
        $user = User::where('peran', 'penyedia')
            ->with('restoran') // relasi: 1 user punya 1 restoran
            ->findOrFail($id);

        $resto = $user->restoran;

        return response()->json([
            'nama' => $user->nama,
            'email' => $user->email,
            'no_hp' => $user->no_hp,

            // Data restoran (jika ada)
            'nama_resto' => $resto?->nama,
            'lokasi' => $resto?->lokasi,
            'status' => $resto?->status,
            'kontak' => $resto?->kontak,
            'nib' => $resto?->nib,
            'surat_halal' => $resto?->surat_halal, // URL atau path file

            'created_at' => $user->created_at->toDateTimeString(),
        ]);
    }


    // PUT /admin/users/{id}
    public function update(Request $request, $id)
    {
    $user = User::where('peran', 'penyedia')->findOrFail($id);

    $validated = $request->validate([
        // Validasi User
        'nama' => 'sometimes|string|max:255',
        'email' => ['sometimes', 'email', Rule::unique('pengguna')->ignore($user->id)],
        'no_hp' => 'sometimes|string|max:20',

        // Validasi Restoran
        'nama_resto' => 'sometimes|string|max:255',
        'lokasi' => 'sometimes|string',
        'status' => 'sometimes|in:buka,tutup',
        'kontak' => 'sometimes|string|max:20',
        'nib' => 'nullable|string|max:100',
        'surat_halal' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // validasi file
    ]);

    DB::beginTransaction();

    try {
        // Update data user
        $user->nama = $validated['nama'] ?? $user->nama;
        $user->email = $validated['email'] ?? $user->email;
        $user->no_hp = $validated['no_hp'] ?? $user->no_hp;

        $user->save();

        // Update data restoran
        $restaurant = Restaurant::where('pemilik_id', $user->id)->first();
        if ($restaurant) {
            $restaurant->nama = $validated['nama_resto'] ?? $restaurant->nama;
            $restaurant->lokasi = $validated['lokasi'] ?? $restaurant->lokasi;
            $restaurant->status = $validated['status'] ?? $restaurant->status;
            $restaurant->kontak = $validated['kontak'] ?? $restaurant->kontak;
            $restaurant->nib = $validated['nib'] ?? $restaurant->nib;

            if ($request->hasFile('surat_halal')) {
                // Simpan file surat halal ke storage
                $path = $request->file('surat_halal')->store('surat_halal', 'public');
                $restaurant->surat_halal = $path;
            }

            $restaurant->save();
        }

        DB::commit();

        return new RestaurantResource(
            $restaurant->load(['owner', 'tables', 'menus', 'jamOperasional'])
        );
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Gagal memperbarui data.',
            'error' => $e->getMessage()
        ], 500);
    }
    }

    // DELETE /admin/users/{id}
    public function destroy($id)
    {
    $user = User::where('peran', 'penyedia')->findOrFail($id);

    DB::beginTransaction();

    try {
        // Hapus restoran terkait dengan pemilik
        $restaurant = Restaurant::where('pemilik_id', $user->id)->first();
        if ($restaurant) {
            $restaurant->delete(); // Hapus restoran
        }

        // Hapus user (pemilik)
        $user->delete();

        DB::commit();

        return response()->json(['message' => 'User dan restoran berhasil dihapus']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Gagal menghapus data.',
            'error' => $e->getMessage()
        ], 500);
    }
    }

        public function toggleRecommendation(Request $request, $id)
    {
        $resto = Restaurant::findOrFail($id);

        // Validasi input
        $validated = $request->validate([
            'is_recommended' => 'required|boolean',
        ]);

        // Update status
        $resto->is_recommended = $validated['is_recommended'];
        $resto->save();

        return response()->json([
            'message' => 'Status rekomendasi berhasil diperbarui.',
            'data' => $resto
        ]);
    }

    public function adminIndex()
    {
        $restaurants = Restaurant::select('id', 'nama', 'deskripsi', 'lokasi as alamat', 'foto', 'is_recommended as sematkan')
                        ->orderBy('created_at', 'desc')
                        ->paginate(10); // bisa pakai pagination

        return response()->json($restaurants);
    }

    public function adminProfileShow($id)
    {
        $user = User::where('peran', 'admin')->findOrFail($id);

        return response()->json([
            'nama' => $user->nama,
            'email' => $user->email,
            'no_hp' => $user->no_hp,
        ]);


    }


    //edit profil field
        public function adminProfileUpdate(Request $request, $id)
    {
        $user = User::where('peran', 'admin')->findOrFail($id);


        $validated = $request->validate([
            'nama' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('pengguna')->ignore($user->id),
            ],
            'no_hp' => 'sometimes|string|max:20',
            'kata_sandi' => 'nullable|string',
            'kata_sandi_baru' => 'nullable|string|min:8|confirmed',
        ]);


        if (isset($validated['nama'])) $user->nama = $validated['nama'];
        if (isset($validated['email'])) $user->email = $validated['email'];
        if (isset($validated['no_hp'])) $user->no_hp = $validated['no_hp'];

        // Password update logic
        if (!empty($validated['kata_sandi_baru'])) {
            if (!Hash::check($validated['kata_sandi'], $user->kata_sandi)) {
                return response()->json(['message' => 'Password lama salah'], 422);
            }
            $user->kata_sandi = Hash::make($validated['kata_sandi_baru']);
        }

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => [
                'nama' => $user->nama,
                'email' => $user->email,
                'no_hp' => $user->no_hp,
            ],
        ]);
    }



}
