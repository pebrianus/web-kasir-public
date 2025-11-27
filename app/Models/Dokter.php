<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;
    protected $connection = 'simgos_master';
    protected $table = 'dokter';
    protected $primaryKey = 'ID';
    public $timestamps = false;
}
