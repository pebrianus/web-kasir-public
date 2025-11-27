<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiskonSimgosToKasirTagihanHead extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('kasir_tagihan_head', function (Blueprint $table) {
        // Menambahkan kolom diskon_simgos setelah total_asli_simgos
        $table->decimal('diskon_simgos', 15, 2)->default(0)->after('total_asli_simgos');
    });
}

public function down()
{
    Schema::table('kasir_tagihan_head', function (Blueprint $table) {
        $table->dropColumn('diskon_simgos');
    });
}
}
