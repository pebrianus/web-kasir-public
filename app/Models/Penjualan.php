<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    // Pakai koneksi khusus
    protected $connection = 'simgos_penjualan';

    // Nama tabel
    protected $table = 'penjualan';

    // Primary key
    protected $primaryKey = 'NOMOR';

    // Primary key bukan auto increment
    public $incrementing = false;

    // Primary key bukan integer
    protected $keyType = 'string';

    // Tidak ada created_at & updated_at
    public $timestamps = false;

    // Field yang bisa diisi mass assignment
    protected $fillable = [
        'NOMOR',
        'RUANGAN',
        'PENGUNJUNG',
        'JENIS',
        'DOKTER',
        'KETERANGAN',
        'TANGGAL',
        'OLEH',
        'STATUS',
    ];

    // Casting tipe data
    protected $casts = [
        'TANGGAL' => 'datetime',
        'JENIS' => 'integer',
        'OLEH' => 'integer',
        'STATUS' => 'integer',
    ];

    public function detil()
    {
        return $this->hasMany(PenjualanDetil::class, 'PENJUALAN_ID', 'NOMOR');
    }

}
