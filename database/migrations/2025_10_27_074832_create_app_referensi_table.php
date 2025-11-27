<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppReferensiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_referensi', function (Blueprint $table) {
            $table->bigIncrements('TABEL_ID'); // ID unik per baris
            $table->integer('JENIS');          // Kategori (1 = Metode Bayar)
            $table->integer('ID');             // ID item per kategori (1 = Tunai)
            $table->string('DESKRIPSI', 100);
            $table->boolean('STATUS')->default(true);

            // Buat index agar pencarian berdasarkan JENIS dan ID cepat
            $table->index(['JENIS', 'ID']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_referensi');
    }
}
