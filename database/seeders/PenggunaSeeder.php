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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $admin->id = (string) Str::uuid();
        $admin->save();
        $admin->assignRole('admin');

        // Penyedia
        $penyedia = new User([
            'nama' => 'Penyedia Restoran',
            'email' => 'penyedia@example.com',
            'kata_sandi' => Hash::make('penyedia123'),
            'no_hp' => '081234567891',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $penyedia->id = (string) Str::uuid();
        $penyedia->save();
        $penyedia->assignRole('penyedia');

        // Pemesan
        $pemesan = new User([
            'nama' => 'Pemesan Makan',
            'email' => 'pemesan@example.com',
            'kata_sandi' => Hash::make('pemesan123'),
            'no_hp' => '081234567892',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $pemesan->id = (string) Str::uuid();
        $pemesan->save();
        $pemesan->assignRole('pemesan');
    }
}
