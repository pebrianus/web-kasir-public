<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifTindakan extends Model
{
    use HasFactory;

    protected $connection = 'simgos_master';
    protected $table = 'tarif_tindakan';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'TINDAKAN',
        'KELAS',
        'ADMINISTRASI',
        'SARANA',
        'BHP',
        'DOKTER_OPERATOR',
        'DOKTER_ANASTESI',
        'DOKTER_LAINNYA',
        'PENATA_ANASTESI',
        'PARAMEDIS',
        'NON_MEDIS',
        'TARIF',
        'TANGGAL',
        'TANGGAL_SK',
        'NOMOR_SK',
        'OLEH',
        'STATUS',
    ];

    protected $casts = [
        'ID' => 'integer',
        'TINDAKAN' => 'integer',
        'KELAS' => 'integer',
        'ADMINISTRASI' => 'integer',
        'SARANA' => 'integer',
        'BHP' => 'integer',
        'DOKTER_OPERATOR' => 'integer',
        'DOKTER_ANASTESI' => 'integer',
        'DOKTER_LAINNYA' => 'integer',
        'PENATA_ANASTESI' => 'integer',
        'PARAMEDIS' => 'integer',
        'NON_MEDIS' => 'integer',
        'TARIF' => 'integer',
        'TANGGAL' => 'datetime',
        'TANGGAL_SK' => 'datetime',
        'OLEH' => 'integer',
        'STATUS' => 'integer',
    ];

    /**
     * Relasi ke master tindakan
     */
    public function tindakan()
    {
        return $this->belongsTo(Tindakan::class, 'TINDAKAN', 'ID');
    }
}
