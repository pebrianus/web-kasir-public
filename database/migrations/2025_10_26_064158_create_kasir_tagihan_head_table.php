<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKasirTagihanHeadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kasir_tagihan_head', function (Blueprint $table) {
            $table->id(); // Primary key internal kita
            
            // --- Referensi SIMGOS ---
            $table->string('simgos_tagihan_id', 15)->unique(); // ID tagihan SIMGOS (char 10)
            $table->integer('simgos_norm');
            $table->string('simgos_nopen', 20)->nullable(); // Nomor pendaftaran
            
            // --- Data Snapshot (Denormalisasi) ---
            $table->string('nama_pasien', 100);
            $table->string('nama_ruangan', 100);
            $table->string('nama_dokter', 150);
            $table->string('nama_asuransi', 100);
            $table->dateTime('simgos_tanggal_tagihan');

            // --- Data Keuangan ---
            $table->decimal('total_asli_simgos', 15, 2)->default(0);
            $table->decimal('total_bayar_pasien', 15, 2)->default(0);
            $table->decimal('total_bayar_asuransi', 15, 2)->default(0);
            
            // --- Status Internal Kita ---
            $table->string('status_kasir', 20)->default('draft'); // mis: 'draft', 'final', 'lunas'
            
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kasir_tagihan_head');
    }
}
