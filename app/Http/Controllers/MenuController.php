<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MenuController extends Controller
{
        public function index(Request $request)
    {
        try {
            $restoranId = $request->user()->restoran->id;

            $menus = Menu::where('restoran_id', $restoranId)->get();

            return response()->json([
                'status' => 'success',
                'data' => $menus
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil daftar menu: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil menu.',
            ], 500);
        }
    }

        public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $menu = Menu::where('id', $id)->where('restoran_id', $user->restoran->id)->first();

            if (!$menu) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Menu tidak ditemukan atau bukan milik Anda.',
                ], 404);
            }

            $menu->foto_url = $menu->foto ? url('menu/' . $menu->foto) : null;

            return response()->json([
                'status' => 'success',
                'data' => $menu
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan menu: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil detail menu.',
            ], 500);
        }
    }

        public function store(Request $request)
    {
        try {
            $request->validate([
                'nama' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'harga' => 'required|numeric',
                'jenis' => 'required|in:makanan,minuman',
                'status' => 'nullable|in:tersedia,tidak_tersedia',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'highlight' => 'nullable|boolean',
            ]);

            $user = $request->user();

            if (!$user->restoran) {
                return response()->json(['status' => 'error', 'message' => 'Restoran tidak ditemukan.'], 404);
            }

            $filename = null;
            if ($request->hasFile('foto')) {
                $file = $request->file('foto');
                $filename = 'foto' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('menu'), $filename);
            }

            $menu = Menu::create([
                'id' => Str::uuid(),
                'restoran_id' => $user->restoran->id,
                'nama' => $request->nama,
                'deskripsi' => $request->deskripsi,
                'harga' => $request->harga,
                'jenis' =>  $request->jenis,
                'status' => $request->status ?? 'tersedia',
                'foto' => $filename,
                'highlight' => $request->highlight ?? false,
            ]);

            $menu->foto_url = $filename ? url('menu/' . $filename) : null;

            return response()->json([
                'status' => 'success',
                'message' => 'Menu berhasil ditambahkan.',
                'data' => $menu
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menambahkan menu: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menambahkan menu.',
            ], 500);
        }
    }


        public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'nama' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'harga' => 'required|numeric',
                'jenis' => 'required|in:makanan,minuman',
                'status' => 'required|in:tersedia,tidak_tersedia',
                'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
                'highlight' => 'nullable|boolean',
            ]);

            $user = $request->user();
            $menu = Menu::where('id', $id)->where('restoran_id', $user->restoran->id)->first();

            if (!$menu) {
                return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan atau bukan milik Anda.'], 404);
            }

            if ($request->hasFile('foto')) {
                $file = $request->file('foto');
                $filename = 'foto' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('menu'), $filename);
                $menu->foto = $filename;
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
            $menu->foto_url = $menu->foto ? url('menu/' . $menu->foto) : null;

            return response()->json([
                'status' => 'success',
                'message' => 'Menu berhasil diperbarui.',
                'data' => $menu
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui menu: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui menu.',
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $menu = Menu::where('id', $id)->where('restoran_id', $user->restoran->id)->first();

            if (!$menu) {
                return response()->json(['status' => 'error', 'message' => 'Menu tidak ditemukan atau bukan milik Anda.'], 404);
            }

            $menu->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Menu berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menghapus menu: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus menu.',
            ], 500);
        }
    }

}
