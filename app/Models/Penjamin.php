<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjamin extends Model
{
    use HasFactory;
    protected $connection = 'simgos_pendaftaran';
    protected $table = 'penjamin';
    public $timestamps = false;
    // Tabel ini mungkin tidak punya primary key unik, tidak apa-apa
}
