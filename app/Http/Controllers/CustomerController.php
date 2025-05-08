<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Resources\UserResource;

class CustomerController extends Controller
{
    // GET /admin/customers
    public function index()
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
