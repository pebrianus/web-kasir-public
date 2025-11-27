<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKasirPembayaranTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kasir_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasir_tagihan_head_id')->constrained('kasir_tagihan_head');
            $table->foreignId('user_id')->constrained('users');
            
            // INI PERUBAHANNYA:
            // Kolom ini akan menyimpan ID item (1=Tunai, 2=Qris)
            $table->integer('metode_bayar_id'); 

            $table->decimal('nominal_bayar', 15, 2);
            $table->timestamps(); // tanggal bayar
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kasir_pembayaran');
    }
}
