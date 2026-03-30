<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kasir_piutang_pembayaran', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kasir_tagihan_piutang_id')
                  ->constrained('kasir_tagihan_piutang')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->decimal('nominal_bayar', 15, 2);
            $table->decimal('nominal_sisa_sebelum', 15, 2);
            $table->decimal('nominal_sisa_sesudah', 15, 2);

            $table->date('tanggal_bayar');

            $table->enum('status_sebelum', ['outstanding', 'sebagian', 'lunas']);
            $table->enum('status_sesudah', ['outstanding', 'sebagian', 'lunas']);

            $table->text('keterangan')->nullable();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->restrictOnDelete()
                  ->cascadeOnUpdate();

            $table->timestamps();

            $table->index('kasir_tagihan_piutang_id');
            $table->index('tanggal_bayar');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kasir_piutang_pembayaran');
    }
};
