<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKasirPenjualanHeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kasir_penjualan_heads', function (Blueprint $table) {
            $table->id();
            // ID dari SIMGOS (Contoh: '2602190001')
            $table->string('simgos_penjualan_id')->unique();

            $table->string('nama_pengunjung')->nullable();
            $table->tinyInteger('jenis_penjualan')->comment('1: Resep, 2: Bebas');
            $table->string('nama_dokter')->nullable();
            $table->text('keterangan')->nullable();
            $table->dateTime('simgos_tanggal');

            // Total tagihan dari tabel pembayaran.tagihan
            $table->decimal('total_tagihan', 15, 2)->default(0);

            // Status di aplikasi kasir lokal kita
            $table->string('status_kasir')->default('draft')->comment('draft, lunas, batal');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kasir_penjualan_heads');
    }
}
