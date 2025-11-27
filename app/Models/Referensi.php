<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referensi extends Model
{
    use HasFactory;
    protected $connection = 'simgos_master';
    protected $table = 'referensi';
    protected $primaryKey = 'TABEL_ID';
    public $timestamps = false;
}
