<?php

namespace App\Http\Controllers;
use App\Models\Restaurant;
use Illuminate\Http\Request;

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
}
