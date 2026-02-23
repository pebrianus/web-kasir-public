<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKasirPenjualanDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kasir_penjualan_details', function (Blueprint $table) {
            $table->id();

            // Relasi ke head (jika head dihapus, detail ikut terhapus)
            $table->foreignId('kasir_penjualan_head_id')
                ->constrained('kasir_penjualan_heads')
                ->onDelete('cascade');

            $table->integer('simgos_barang_id');
            $table->string('nama_barang');
            $table->decimal('qty', 10, 2)->default(0);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);

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
        Schema::dropIfExists('kasir_penjualan_details');
    }
}
