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

        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'kata_sandi' => 'nullable|string',
            'kata_sandi_baru' => 'nullable|string|min:8|confirmed',
        ]);

        $user->nama = $request->nama;
        $user->email = $request->email;

        if ($request->filled('kata_sandi') && $request->filled('kata_sandi_baru')) {
            if (Hash::check($request->kata_sandi, $user->password)) {
                $user->password = Hash::make($request->kata_sandi_baru);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Password lama salah, password tidak diubah.'
                ], 400);
            }
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'nama' => $user->nama,
                'email' => $user->email,
            ]
        ]);
    }


    // GET /admin/customers/{id}
       public function show(string $id)
{
    $user = User::where('peran', 'pemesan')->where('id', $id)->firstOrFail();

    return new UserResource($user);
}



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
}
