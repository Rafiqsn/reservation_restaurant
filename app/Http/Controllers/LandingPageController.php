<?php

namespace App\Http\Controllers;
use App\Models\Restaurant;
use App\Http\Resources\RestaurantResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingPageController extends Controller
{
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
        $restoran = Restaurant::with([
            'owner',
            'jamOperasional',
            'menus' => fn($q) => $q->where('highlight', true)->limit(3),
            'ulasan.reservasi.user'
        ])->findOrFail($id);

        return new RestaurantResource($restoran);
    }



            public function search(Request $request)
    {
        $search = $request->query('search');

        $query = Restaurant::with('ulasan'); // eager load ulasan untuk avg rating

        if ($search) {
            $query->where('nama', 'like', '%' . $search . '%');
        }

        $restoran = $query->paginate(10);

        return RestaurantResource::collection($restoran);
    }



        public function rekomendasi()
    {
        $recommended = Restaurant::select('restoran.id', 'nama', 'deskripsi', 'foto')
            ->leftJoin(DB::raw('(SELECT restoran_id, AVG(rating) as rata_rata_rating FROM ulasan GROUP BY restoran_id) as u'), 'restoran.id', '=', 'u.restoran_id')
            ->where('is_recommended', true)
            ->addSelect('u.rata_rata_rating')
            ->take(6)
            ->get();

        return response()->json([
            'recommended' => $recommended,
        ]);
    }


        public function terdekat(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|numeric', // radius km, default 10 km
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $radius = $request->radius ?? 10; // default 10 km

        $restoranTerdekat = DB::table('restoran')
            ->selectRaw("*,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance", [$latitude, $longitude, $latitude])
            ->where('status', 'buka')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $restoranTerdekat,
        ]);
    }


}
