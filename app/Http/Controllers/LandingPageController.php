<?php

namespace App\Http\Controllers;
use App\Models\Restaurant;
use App\Http\Resources\RestaurantResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LandingPageController extends Controller
{
    public function index(Request $request)
    {
        try {
            $recommended = Restaurant::with('fotoTambahan')
                ->where('is_recommended', true)
                ->take(6)
                ->get();

            $newest = Restaurant::with('fotoTambahan')
                ->orderBy('created_at', 'desc')
                ->take(6)
                ->get();

            $nearest = collect();
            if ($request->has(['lat', 'lng'])) {
                $lat = $request->lat;
                $lng = $request->lng;

                $nearest = Restaurant::with('fotoTambahan')
                    ->selectRaw("
                        *,
                        (6371 * acos(
                            cos(radians(?)) * cos(radians(latitude)) *
                            cos(radians(longitude) - radians(?)) +
                            sin(radians(?)) * sin(radians(latitude))
                        )) AS distance", [$lat, $lng, $lat])
                    ->orderBy('distance')
                    ->take(6)
                    ->get();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data restoran berhasil diambil',
                'data' => [
                    'recommended' => RestaurantResource::collection($recommended),
                    'newest' => RestaurantResource::collection($newest),
                    'nearest' => RestaurantResource::collection($nearest),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal ambil data index restoran: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data restoran',
                'data' => null,
            ], 500);
        }
    }

        public function show($id)
    {
        try {
            $restoran = Restaurant::with([
                'owner',
                'jamOperasional',
                'tables',
                'menus' => fn($q) => $q->where('highlight', true)->limit(3),
                'ulasan.reservasi.user',
                'fotoTambahan'
            ])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Detail restoran ditemukan',
                'data' => new RestaurantResource($restoran),
            ]);
        } catch (\Exception $e) {
            Log::error("Gagal menampilkan restoran ID $id: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menampilkan detail restoran',
                'data' => null,
            ], 500);
        }
    }


    public function search(Request $request)
    {
        try {
            $search = $request->query('search');

            $query = Restaurant::with(['ulasan', 'fotoTambahan']);

            if ($search) {
                $query->where('nama', 'like', '%' . $search . '%');
            }

            $restoran = $query->paginate(10);

            return response()->json([
                'status' => 'success',
                'message' => 'Hasil pencarian restoran',
                'data' => RestaurantResource::collection($restoran),
            ]);
        } catch (\Exception $e) {
            Log::error("Gagal melakukan pencarian restoran: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mencari restoran',
                'data' => null,
            ], 500);
        }
    }



    public function rekomendasi()
    {
        try {
            $recommended = Restaurant::with('fotoTambahan')
                ->leftJoin(DB::raw('(SELECT restoran_id, AVG(rating) as rata_rata_rating FROM ulasan GROUP BY restoran_id) as u'), 'restoran.id', '=', 'u.restoran_id')
                ->where('is_recommended', true)
                ->addSelect('restoran.*', 'u.rata_rata_rating')
                ->take(6)
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar restoran rekomendasi',
                'data' => RestaurantResource::collection($recommended),
            ]);
        } catch (\Exception $e) {
            Log::error("Gagal mengambil restoran rekomendasi: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil restoran rekomendasi',
                'data' => null,
            ], 500);
        }
    }


    public function terdekat(Request $request)
    {
        try {
            $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'radius' => 'nullable|numeric',
            ]);

            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $radius = $request->radius ?? 10;

            $restoranTerdekat = Restaurant::with('fotoTambahan')
                ->selectRaw("*, (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )
                ) AS distance", [$latitude, $longitude, $latitude])
                ->where('status', 'buka')
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar restoran terdekat ditemukan',
                'data' => RestaurantResource::collection($restoranTerdekat),
            ]);
        } catch (\Exception $e) {
            Log::error("Gagal mengambil restoran terdekat: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil restoran terdekat',
                'data' => null,
            ], 500);
        }
    }




}
