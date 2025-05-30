<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $restoranId = $request->user()->restoran->id;

        $menus = Menu::where('restoran_id', $restoranId)->get();

        return response()->json($menus);
    }

            public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'required|numeric',
            'jenis' => 'required|in:makanan,minuman',
            'status' => 'nullable|in:tersedia,tidak_tersedia',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'highlight' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        if (!$user->restoran) {
            return response()->json(['message' => 'Restoran tidak ditemukan untuk user ini.'], 404);
        }

       $filename = null;

        // Upload file
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = 'foto' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('foto'), $filename); // Simpan ke public/menu
        }

        $menu = Menu::create([
            'id' => Str::uuid(),
            'restoran_id' => $user->restoran->id,
            'nama' => $request->nama,
            'deskripsi' => $request->deskripsi,
            'harga' => $request->harga,
            'jenis' =>  $request->jenis,
            'status' => $request->status ?? 'tersedia',
            'foto' => $filename ? 'menu/' . $filename : null,
            'highlight' => $request->highlight ?? false,
        ]);

        return response()->json([
            'message' => 'Menu berhasil ditambahkan.',
            'data' => $menu
        ]);
    }



        public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga' => 'required|numeric',
            'jenis' => 'required|in:makanan,minuman',
            'status' => 'required|in:tersedia,tidak_tersedia',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'highlight' => 'nullable|boolean',

        ]);

        $user = $request->user();
        $menu = Menu::where('id', $id)->where('restoran_id', $user->restoran->id)->first();

        if (!$menu) {
            return response()->json(['message' => 'Menu tidak ditemukan atau bukan milik Anda'], 404);
        }

        // Update foto jika ada
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('menu', 'public');
            $menu->foto = $fotoPath;
        }

        $menu->nama = $request->nama;
        $menu->deskripsi = $request->deskripsi;
        $menu->harga = $request->harga;
        $menu->jenis = $request->jenis;
        $menu->status = $request->status;
         if ($request->has('highlight')) {
        $menu->highlight = $request->highlight;
    }
        $menu->save();

        return response()->json(['message' => 'Menu berhasil diperbarui', 'data' => $menu]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $menu = Menu::where('id', $id)->where('restoran_id', $user->restoran->id)->first();

        if (!$menu) {
            return response()->json(['message' => 'Menu tidak ditemukan atau bukan milik Anda'], 404);
        }

        $menu->delete();

        return response()->json(['message' => 'Menu berhasil dihapus']);
    }

}
