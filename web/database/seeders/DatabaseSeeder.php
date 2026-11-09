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

        $toko = $user->collections()->where('name', 'Toko')->first();

        $contoh = <<<'PY'
        from selenium import webdriver
        from selenium.webdriver.common.by import By
        from selenium.webdriver.support.ui import WebDriverWait
        from selenium.webdriver.support import expected_conditions as EC


        def setup_driver():
            driver = webdriver.Chrome()
            wait = WebDriverWait(driver, 10)
            return driver, wait


        def test_tambah_produk():
            driver, wait = setup_driver()
            try:
                driver.get("https://toko.example/products/tas-kanvas")
                wait.until(EC.element_to_be_clickable(
                    (By.ID, "add-to-cart")
                )).click()
                driver.get("https://toko.example/cart")
                assert "Tas Kanvas" in wait.until(
                    EC.visibility_of_element_located((By.ID, "cart-items"))
                ).text
            finally:
                driver.quit()
        PY;

        $skrip = [
            'Keranjang belanja' => ['completed', null],
            'Hapus produk' => ['partial', 'Tombol hapus tidak ditemukan di halaman keranjang.'],
            'Login pelanggan' => ['failed', 'Field password tidak ditemukan di halaman login.'],
        ];

        foreach ($skrip as $nama => [$status, $belumSelesai]) {
            $toko->testCases()->where('name', $nama)->first()
                ->llmOutput()->create(['output' => '{}', 'validation_status' => 'valid'])
                ->generatedScript()->create([
                    'content' => $contoh,
                    'status' => $status,
                    'unresolved_step' => $belumSelesai,
                ]);
        }

        $toko->testCases()->where('name', 'Keranjang belanja')->first()
            ->scenarios()->createMany([
                ['name' => 'Menambahkan produk', 'scenario_url' => 'https://toko.example/products/tas-kanvas'],
                ['name' => 'Menghapus produk', 'scenario_url' => 'https://toko.example/cart'],
            ]);

        $user->collections()->create(['name' => 'KaryaRasa']);
        $user->collections()->create(['name' => 'LearnFit']);
    }
}
