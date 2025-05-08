<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PenggunaSeeder extends Seeder
{
    public function run()
    {
        // Admin
        $admin = new User([
            'nama' => 'Admin Utama',
            'email' => 'admin@example.com',
            'kata_sandi' => Hash::make('admin123'),
            'no_hp' => '081234567890',
            'peran' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $admin->id = (string) Str::uuid();
        $admin->save();


        // Penyedia
        $penyedia = new User([
            'nama' => 'Penyedia Restoran',
            'email' => 'penyedia@example.com',
            'kata_sandi' => Hash::make('penyedia123'),
            'no_hp' => '081234567891',
            'peran' => 'penyedia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $penyedia->id = (string) Str::uuid();
        $penyedia->save();


        // Pemesan
        $pemesan = new User([
            'nama' => 'Pemesan Makan',
            'email' => 'pemesan@example.com',
            'kata_sandi' => Hash::make('pemesan123'),
            'no_hp' => '081234567892',
            'peran' => 'pemesan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pemesan->id = (string) Str::uuid();
        $pemesan->save();

        $pemesan = new User([
            'nama' => 'Rafiq',
            'email' => 'rafiqrafiq2006@gmail.com',
            'kata_sandi' => Hash::make('rafiqgans123'),
            'no_hp' => '081234567892',
            'peran' => 'pemesan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pemesan->id = (string) Str::uuid();
        $pemesan->save();

    }
}
