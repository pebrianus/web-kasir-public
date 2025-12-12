<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Farmasi extends Model
{
    // Nama koneksi database (harus sama dengan database.php)
    protected $connection = 'simgos_layanan';

    // Nama tabel
    protected $table = 'farmasi';

    // Primary key
    protected $primaryKey = 'ID';

    // Primary key bukan auto-increment
    public $incrementing = false;

    // Tipe data primary key char
    protected $keyType = 'string';

    // Tidak memakai timestamps (created_at, updated_at)
    public $timestamps = false;

    // Field yang boleh diisi
    protected $fillable = [
        'ID',
        'KUNJUNGAN',
        'FARMASI',
        'TANGGAL',
        'JUMLAH',
        'BON',
        'ATURAN_PAKAI',
        'SIGNA1',
        'SIGNA2',
        'DOSIS',
        'KETERANGAN',
        'RACIKAN',
        'GROUP_RACIKAN',
        'PETUNJUK_RACIKAN',
        'JUMLAH_RACIKAN',
        'ALASAN_TIDAK_TERLAYANI',
        'HARI',
        'KLAIM_TERPISAH',
        'TINDAKAN_PAKET',
        'FREKUENSI',
        'RUTE_PEMBERIAN',
        'OLEH',
        'STATUS',
        'REF',
        'ID_ORDER_DETAIL',
        'FLAG',
        'TINDAKAN',
    ];
}
