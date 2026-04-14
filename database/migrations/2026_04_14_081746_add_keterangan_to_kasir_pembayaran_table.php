<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kasir_pembayaran', function (Blueprint $table) {
            $table->string('keterangan')->nullable()->after('nominal_bayar');
        });
    }

    public function down(): void
    {
        Schema::table('kasir_pembayaran', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
