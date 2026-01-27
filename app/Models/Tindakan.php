<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tindakan extends Model
{
    use HasFactory;

    protected $connection = 'simgos_master';
    protected $table = 'tindakan';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'JENIS',
        'NAMA',
        'PRIVACY',
        'KPTL_NO',
        'KPTL_STATUS',
        'KATEGORI',
        'STATUS',
    ];

    protected $casts = [
        'ID' => 'integer',
        'JENIS' => 'integer',
        'PRIVACY' => 'integer',
        'KPTL_STATUS' => 'integer',
        'KATEGORI' => 'integer',
        'STATUS' => 'integer',
    ];
}
