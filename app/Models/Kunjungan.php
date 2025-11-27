<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    use HasFactory;
    protected $connection = 'simgos_pendaftaran';
    protected $table = 'kunjungan';
    protected $primaryKey = 'NOMOR';
    public $incrementing = false;
    public $timestamps = false;
}
