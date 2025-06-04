<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Resources\UserResource;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class CustomerController extends Controller
{
    // GET /admin/customers
    public function CustShow()
    {
        $user = Auth::user(); // user yang login

        // kalau kamu mau pastikan perannya pemesan
        if ($user && $user->peran === 'pemesan') {
            return new UserResource($user);
        }

        // kalau bukan pemesan, bisa return error atau data kosong
        return response()->json([
            'status' => 'error',
            'message' => 'User bukan pemesan atau belum login'
        ], 403);
    }


        public function updateProfile(Request $request, $id)
    {
        $user = User::where('peran', 'pemesan')->findOrFail($id);

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



    public function index()
    {
        try {
            $users = User::where('peran', 'pemesan')->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Daftar pelanggan berhasil diambil.',
                'data' => UserResource::collection($users),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get customers: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil daftar pelanggan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $user = User::where('peran', 'pemesan')->where('id', $id)->firstOrFail();
            return response()->json([
                'status' => 'success',
                'message' => 'Data pelanggan berhasil diambil.',
                'data' => new UserResource($user),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pelanggan tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get customer: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data pelanggan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
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

            return response()->json([
                'status' => 'success',
                'message' => 'Data pelanggan berhasil diperbarui.',
                'data' => new UserResource($user),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pelanggan tidak ditemukan.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update customer: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data pelanggan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::where('peran', 'pemesan')->findOrFail($id);
            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Customer berhasil dihapus.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pelanggan tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete customer: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus pelanggan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
