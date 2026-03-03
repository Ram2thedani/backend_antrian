<?php

namespace Database\Seeders;

use App\Models\Layanan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Petugas TU',
            'email' => 'petugas@kampus.test',
            'password' => Hash::make('password123'),
            'role' => 'petugas',
        ]);
        Layanan::create([
            'nama' => 'Keuangan',
            'kode' => 'TU'
        ]);
    }
}
