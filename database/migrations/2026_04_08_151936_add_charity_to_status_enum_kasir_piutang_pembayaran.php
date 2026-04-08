<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menambahkan 'charity' ke status_sebelum dan status_sesudah
        DB::statement("ALTER TABLE kasir_piutang_pembayaran MODIFY COLUMN status_sebelum ENUM('outstanding', 'sebagian', 'lunas', 'charity') NOT NULL");
        DB::statement("ALTER TABLE kasir_piutang_pembayaran MODIFY COLUMN status_sesudah ENUM('outstanding', 'sebagian', 'lunas', 'charity') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mengembalikan ke pilihan awal (Pastikan tidak ada data 'charity' sebelum rollback agar tidak error)
        DB::statement("ALTER TABLE kasir_piutang_pembayaran MODIFY COLUMN status_sebelum ENUM('outstanding', 'sebagian', 'lunas') NOT NULL");
        DB::statement("ALTER TABLE kasir_piutang_pembayaran MODIFY COLUMN status_sesudah ENUM('outstanding', 'sebagian', 'lunas') NOT NULL");
    }
};
