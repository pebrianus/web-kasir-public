<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPenjualanIdToKasirPembayaransTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kasir_pembayaran', function (Blueprint $table) {
            // Tambahkan kolom baru khusus farmasi (boleh kosong jika ini dari rawat jalan)
            $table->foreignId('kasir_penjualan_head_id')
                ->nullable()
                ->after('kasir_tagihan_head_id')
                ->constrained('kasir_penjualan_heads')
                ->onDelete('cascade');

            // Ubah kolom lama menjadi nullable (agar transaksi farmasi bisa masuk tanpa error)
            $table->unsignedBigInteger('kasir_tagihan_head_id')->nullable()->change();
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
            $table->dropForeign(['kasir_penjualan_head_id']);
            $table->dropColumn('kasir_penjualan_head_id');
            $table->unsignedBigInteger('kasir_tagihan_head_id')->nullable(false)->change();
        });
    }
}
