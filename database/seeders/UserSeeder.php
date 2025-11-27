<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// Panggil Model User kita
use App\Models\User;
// Panggil fitur Hash untuk enkripsi
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Hapus data lama jika ada (opsional, tapi bagus untuk tes ulang)
        User::truncate();

        // Buat user 'pebri' dengan password 'rssi'
        User::create([
            'nama'     => 'Pebrianus Pangaleloe',
            'username' => 'pebri',
            'nip'      => '20250110003', // Ganti NIP jika perlu
            'password' => Hash::make('rssi') // <-- INI KUNCINYA!
        ]);
        User::create([
            'nama'     => 'Endang Ningsih',
            'username' => 'endang',
            'nip'      => '20170620012',
            'password' => Hash::make('123') // Password default kasir
        ]);
        User::create([
            'nama'     => 'Dalianti',
            'username' => 'dali',
            'nip'      => '20250620023',
            'password' => Hash::make('123') // Password default kasir
        ]);
        User::create([
            'nama'     => 'Hellena Yosefha',
            'username' => 'hellena',
            'nip'      => '20240520012',
            'password' => Hash::make('123') // Password default kasir
        ]);
        User::create([
            'nama'     => 'Sri Herliana',
            'username' => 'sri herliana',
            'nip'      => '6307034807960002',
            'password' => Hash::make('123') // Password default kasir
        ]);
        User::create([
            'nama'     => 'Maria Yasintha Jiun',
            'username' => 'maria yasintha',
            'nip'      => '20121120026',
            'password' => Hash::make('123') // Password default kasir
        ]);
    }
}
