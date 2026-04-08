<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE kasir_tagihan_piutang MODIFY COLUMN status ENUM('outstanding', 'sebagian', 'lunas', 'charity') NOT NULL DEFAULT 'outstanding'");
    }

    public function down()
    {
        // Pastikan tidak ada data 'charity' sebelum rollback
        DB::statement("UPDATE kasir_tagihan_piutang SET status = 'outstanding' WHERE status = 'charity'");
        DB::statement("ALTER TABLE kasir_tagihan_piutang MODIFY COLUMN status ENUM('outstanding', 'sebagian', 'lunas') NOT NULL DEFAULT 'outstanding'");
    }
};
