<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSplitColumnsToKasirTagihanDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kasir_tagihan_detail', function (Blueprint $table) {
            // Kolom untuk diisi kasir
            $table->decimal('nominal_ditanggung_asuransi', 15, 2)->default(0)
                ->after('subtotal');

            // Kolom untuk sisa (dihitung otomatis)
            $table->decimal('nominal_ditanggung_pasien', 15, 2)->default(0)
                ->after('nominal_ditanggung_asuransi');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kasir_tagihan_detail', function (Blueprint $table) {
            //
        });
    }
}
