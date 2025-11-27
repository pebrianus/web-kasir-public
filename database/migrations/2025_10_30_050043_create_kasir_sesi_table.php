<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKasirSesiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kasir_sesi', function (Blueprint $table) {
            $table->id();

            // Nama sesi, misal: "Shift Pagi 30-Okt-2025"
            $table->string('nama_sesi', 100)->nullable(); 

            $table->dateTime('waktu_buka');
            $table->dateTime('waktu_tutup')->nullable();

            // Siapa yang membuka dan menutup sesi
            $table->foreignId('dibuka_oleh_user_id')->constrained('users');
            $table->foreignId('ditutup_oleh_user_id')->nullable()->constrained('users');

            // Dihitung otomatis saat 'tutup kasir'
            $table->decimal('total_penerimaan_sistem', 15, 2)->nullable(); 

            // Status sesi: 'BUKA' atau 'TUTUP'
            $table->string('status', 20)->default('BUKA');

            $table->timestamps(); // (created_at dan updated_at)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kasir_sesi');
    }
}
