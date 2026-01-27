<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetugasTindakanMedis extends Model
{
    use HasFactory;

    protected $connection = 'simgos_layanan';
    protected $table = 'petugas_tindakan_medis';
    protected $primaryKey = 'ID';

    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'TINDAKAN_MEDIS',
        'JENIS',
        'MEDIS',
        'KE',
        'STATUS',
    ];

    protected $casts = [
        'ID'              => 'integer',
        'JENIS'           => 'integer',
        'MEDIS'           => 'integer',
        'KE'              => 'integer',
        'STATUS'          => 'integer',
    ];
}
