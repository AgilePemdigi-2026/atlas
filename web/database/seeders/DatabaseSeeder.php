<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->collections()->create(['name' => 'Toko'])->testCases()->createMany([
            ['name' => 'Keranjang belanja', 'user_story' => 'Sebagai pelanggan, saya ingin menambahkan produk ke keranjang.', 'status' => 'completed'],
            ['name' => 'Tambah produk', 'user_story' => 'Sebagai pelanggan, saya ingin menambah produk.', 'status' => 'pending'],
            ['name' => 'Hapus produk', 'user_story' => 'Sebagai pelanggan, saya ingin menghapus produk.', 'status' => 'pending'],
            ['name' => 'Login pelanggan', 'user_story' => 'Sebagai pelanggan, saya ingin masuk ke akun saya.', 'status' => 'failed'],
        ]);

        $user->collections()->create(['name' => 'KaryaRasa']);
        $user->collections()->create(['name' => 'LearnFit']);
    }
}
