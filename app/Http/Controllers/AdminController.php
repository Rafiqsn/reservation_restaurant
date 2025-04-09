<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Resources\UserResource;

class AdminController extends Controller
{
    // GET /admin/users
        public function index()
    {
        $users = User::where('peran', 'penyedia')->get();
        return UserResource::collection($users);
    }


    // POST /admin/users
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
            'peran' => 'penyedia', // ⛔ hanya buat penyedia
        ]);

        return new UserResource($user);
    }

    // GET /admin/users/{id}
    public function show($id)
    {
        $user = User::where('peran', 'penyedia')->findOrFail($id);
        return new UserResource($user);
    }

    // PUT /admin/users/{id}
    public function update(Request $request, $id)
    {
        $user = User::where('peran', 'penyedia')->findOrFail($id);

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

    // DELETE /admin/users/{id}
    public function destroy($id)
    {
        $user = User::where('peran', 'penyedia')->findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus']);
    }
}
