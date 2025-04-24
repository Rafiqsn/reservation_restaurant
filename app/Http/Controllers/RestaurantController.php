<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Resources\RestaurantResource;

class RestaurantController extends Controller
{
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
        $restaurant = Restaurant::with(['owner', 'tables', 'menus', 'jamOperasional'])->findOrFail($id);
        return new RestaurantResource($restaurant);
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
}
