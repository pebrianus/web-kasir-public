<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJenisKasirToKasirSesiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kasir_sesi', function (Blueprint $table) {
            $table->tinyInteger('jenis_kasir')->default(1)->after('nama_sesi');
        });
    }

    public function down()
    {
        Schema::table('kasir_sesi', function (Blueprint $table) {
            $table->dropColumn('jenis_kasir');
        });
    }

}
