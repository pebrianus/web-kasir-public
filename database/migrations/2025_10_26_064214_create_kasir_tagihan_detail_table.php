<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKasirTagihanDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kasir_tagihan_detail', function (Blueprint $table) {
            $table->id(); // Primary key internal
            
            // Foreign key ke tabel 'head' kita
            $table->foreignId('kasir_tagihan_head_id')->constrained('kasir_tagihan_head')->onDelete('cascade');

            // --- Referensi SIMGOS (untuk audit) ---
            $table->string('simgos_ref_id', 20)->nullable(); // REF_ID dari SIMGOS
            $table->tinyInteger('simgos_jenis_tarif'); // 1=Admin, 3=Tindakan, 4=Farmasi

            // --- Data Snapshot (DENORMALISASI) ---
            $table->string('deskripsi_item', 255); // "Konsul Dokter", "Infus NaCl"
            $table->decimal('qty', 10, 2); // JUMLAH dari SIMGOS
            $table->decimal('harga_satuan', 15, 2); // TARIF dari SIMGOS
            $table->decimal('subtotal', 15, 2); // (qty * harga_satuan)
            $table->decimal('diskon_item', 15, 2)->default(0); // Kolom diskon Anda

            // $table->tinyInteger('status_item'); // Anda sebutkan 'STATUS' di rincian
            
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
        Schema::dropIfExists('kasir_tagihan_detail');
    }
}
