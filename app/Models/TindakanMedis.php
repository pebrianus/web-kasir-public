<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TindakanMedis extends Model
{
    use HasFactory;

    protected $connection = 'simgos_layanan';
    protected $table = 'tindakan_medis';
    protected $primaryKey = 'ID';

    public $incrementing = false; // karena PK CHAR(11)
    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'ID',
        'KUNJUNGAN',
        'TINDAKAN',
        'TANGGAL',
        'VERIFIKASI',
        'VERIFIKASI_OLEH',
        'VERIFIKASI_TANGGAL',
        'OLEH',
        'STATUS',
        'OTOMATIS',
    ];

    protected $casts = [
        'TINDAKAN'           => 'integer',
        'VERIFIKASI'         => 'integer',
        'VERIFIKASI_OLEH'    => 'integer',
        'OLEH'               => 'integer',
        'STATUS'             => 'integer',
        'OTOMATIS'           => 'integer',
        'TANGGAL'            => 'datetime',
        'VERIFIKASI_TANGGAL' => 'datetime',
    ];
}
