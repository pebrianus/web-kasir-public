<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppReferensiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('app_referensi')->insert([
            // --- JENIS = 1 (Metode Bayar) ---
            ['JENIS' => 1, 'ID' => 1, 'DESKRIPSI' => 'Tunai', 'STATUS' => true],
            ['JENIS' => 1, 'ID' => 2, 'DESKRIPSI' => 'Debit', 'STATUS' => true],
            ['JENIS' => 1, 'ID' => 3, 'DESKRIPSI' => 'QRIS', 'STATUS' => true],
            ['JENIS' => 1, 'ID' => 4, 'DESKRIPSI' => 'CO Karyawan', 'STATUS' => true],
            ['JENIS' => 1, 'ID' => 5, 'DESKRIPSI' => 'Pastoran/Suster', 'STATUS' => true],
        ]);
    }
}
