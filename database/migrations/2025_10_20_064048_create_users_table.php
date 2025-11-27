<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Ini adalah 'ID auto integer, primary key'
            
            // Kolom 'username' (standar Laravel)
            $table->string('username', 50)->unique(); 
            
            // Kolom 'password' (standar Laravel)
            $table->string('password'); 
            
            $table->string('nama', 75);
            $table->string('nip', 30)->nullable(); // ->nullable() artinya boleh kosong
            
            $table->rememberToken(); // Kolom untuk fitur "Ingat Saya"
            $table->timestamps(); // Kolom 'created_at' dan 'updated_at'
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
