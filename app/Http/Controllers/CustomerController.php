<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Resources\UserResource;
use App\Models\Restaurant;
use App\Http\Resources\RestaurantResource;

class CustomerController extends Controller
{
    // GET /admin/customers
    public function index(Request $request)
    {
        // Rekomendasi Restoran
        $recommended = Restaurant::select('nama', 'deskripsi', 'foto')
            ->where('is_recommended', true)
            ->take(6)
            ->get();

        // Restoran Terbaru
        $newest = Restaurant::select('nama', 'deskripsi', 'foto')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // Restoran Terdekat (jika koordinat tersedia)
        $nearest = collect(); // Kosongkan default-nya
        if ($request->has(['lat', 'lng'])) {
            $lat = $request->lat;
            $lng = $request->lng;

            $nearest = Restaurant::selectRaw("
                    nama, deskripsi, foto,
                    ( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) )
                    * cos( radians( longitude ) - radians(?) ) + sin( radians(?) )
                    * sin( radians( latitude ) ) ) ) AS distance", [$lat, $lng, $lat])
                ->orderBy('distance')
                ->take(6)
                ->get();
        }

        return response()->json([
            'recommended' => $recommended,
            'newest' => $newest,
            'nearest' => $nearest,
        ]);
    }

        public function show($id)
    {
        $resto = Restaurant::with(['jamOperasional', 'menus' => function($q) {
            $q->where('highlight', true);
        }])->findOrFail($id);

        $jamHariIni = $resto->jamOperasional
            ->where('hari', now()->format('l')) // ambil hari ini
            ->first();

        return response()->json([
            'id' => $resto->id,
            'nama' => $resto->nama,
            'alamat' => $resto->alamat,
            'deskripsi' => $resto->deskripsi,
            'jam_operasional' => $jamHariIni ? [
                'buka' => $jamHariIni->buka,
                'tutup' => $jamHariIni->tutup,
                'status' => $this->cekStatusBuka($jamHariIni)
            ] : null,
            'foto' => json_decode($resto->foto),
            'highlight_menu' => $resto->menus->map(function($menu) {
                return [
                    'nama' => $menu->nama,
                    'harga' => $menu->harga,
                    'foto' => $menu->foto
                ];
            }),
            'lokasi' => [
                'latitude' => $resto->latitude,
                'longitude' => $resto->longitude
            ]
        ]);
    }


    private function cekStatusBuka($jam)
    {
        $now = now()->format('H:i');
        return ($now >= $jam->buka && $now <= $jam->tutup) ? 'buka' : 'tutup';
    }

     public function indexuser()
    {
        $users = User::where('peran', 'pemesan')->get();
        return UserResource::collection($users);
    }

    // POST /admin/customers
    /*
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pengguna,email',
            'kata_sandi' => 'required|string|min:6',
            'no_hp' => 'required|string|max:20',
        ]);

        $user = User::create([
            'id' => Str::uuid(),
            'nama' => $validated['nama'],
            'email' => $validated['email'],
            'kata_sandi' => Hash::make($validated['kata_sandi']),
            'no_hp' => $validated['no_hp'],
            'peran' => 'customer', // hanya bisa membuat customer
        ]);

        return new UserResource($user);
    }*/

    // GET /admin/customers/{id}


    // PUT /admin/customers/{id}
    public function update(Request $request,string $id)
    {
        $user = User::where('peran', 'pemesan')->where('id', $id)->firstOrFail();

        $validated = $request->validate([
            'nama' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('pengguna')->ignore($user->id)],
            'kata_sandi' => 'nullable|string|min:6',
            'no_hp' => 'sometimes|string|max:20',
        ]);

        $user->nama = $validated['nama'] ?? $user->nama;
        $user->email = $validated['email'] ?? $user->email;
        $user->no_hp = $validated['no_hp'] ?? $user->no_hp;

        if (!empty($validated['kata_sandi'])) {
            $user->kata_sandi = Hash::make($validated['kata_sandi']);
        }

        $user->save();

        return new UserResource($user);
    }

    // DELETE /admin/customers/{id}
    public function destroy($id)
    {
        $user = User::where('peran', 'pemesan')->findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Customer berhasil dihapus']);
    }

 public function search(Request $request)
    {
        $search = $request->query('search'); // ambil keyword pencarian dari query string

        $restoran = Restaurant::query()
            ->when($search, function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                      ->orWhere('deskripsi', 'like', "%{$search}%");
            })
            ->get();

        return response()->json([
            'message' => 'Daftar restoran berhasil diambil',
            'data' => $restoran
        ]);
    }

}
