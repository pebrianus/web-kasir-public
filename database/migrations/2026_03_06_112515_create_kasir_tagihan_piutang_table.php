<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKasirTagihanPiutangTable extends Migration
{
    public function up()
    {
        Schema::create('kasir_tagihan_piutang', function (Blueprint $table) {
            $table->id();

            // Relasi ke tagihan utama
            $table->foreignId('kasir_tagihan_head_id')
                  ->constrained('kasir_tagihan_head')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            // Snapshot data pasien & asuransi
            $table->string('simgos_tagihan_id', 15);
            $table->integer('simgos_norm');
            $table->string('nama_pasien', 100);
            $table->string('nama_asuransi', 100);

            // Nominal
            $table->decimal('total_tagihan_asuransi', 15, 2)->default(0);
            $table->decimal('nominal_piutang', 15, 2)->default(0);
            $table->decimal('nominal_terbayar', 15, 2)->default(0);
            $table->decimal('nominal_sisa', 15, 2)->default(0);

            // Status & penagihan
            $table->enum('status', ['outstanding', 'sebagian', 'lunas'])->default('outstanding');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->date('tanggal_lunas')->nullable();

            // Audit
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreignId('kasir_sesi_id')
                  ->constrained('kasir_sesi')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->text('keterangan')->nullable();

            $table->timestamps();

            // Index
            $table->index('status');
            $table->index('nama_asuransi');
            $table->index('tanggal_jatuh_tempo');
        });
    }

    public function down()
    {
        Schema::table('kasir_tagihan_piutang', function (Blueprint $table) {
            $table->dropForeign(['kasir_tagihan_head_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['kasir_sesi_id']);
        });

        Schema::dropIfExists('kasir_tagihan_piutang');
    }
}
