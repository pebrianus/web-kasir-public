<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKasirSesiIdToKasirPembayaranTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kasir_pembayaran', function (Blueprint $table) {
            // Tambahkan kolom foreign key baru
            $table->foreignId('kasir_sesi_id')
                  ->nullable() // Boleh null (opsional, tapi aman)
                  ->after('kasir_tagihan_head_id') // Posisi di tabel (opsional)
                  ->constrained('kasir_sesi'); // Hubungkan ke tabel 'kasir_sesi'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kasir_pembayaran', function (Blueprint $table) {
            //
        });
    }
}
