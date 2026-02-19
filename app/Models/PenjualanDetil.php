<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanDetil extends Model
{
    // Pakai koneksi khusus (sama seperti Penjualan)
    protected $connection = 'simgos_penjualan';

    // Nama tabel
    protected $table = 'penjualan_detil';

    // Primary key
    protected $primaryKey = 'ID';

    // Auto increment (default true, tapi kita tulis biar jelas)
    public $incrementing = true;

    // Primary key integer
    protected $keyType = 'int';

    // Tidak ada timestamps
    public $timestamps = false;

    // Field mass assignment
    protected $fillable = [
        'PENJUALAN_ID',
        'BARANG',
        'HARGA_BARANG',
        'ATURAN_PAKAI',
        'JUMLAH',
        'MARGIN',
        'PPN',
        'STATUS',
    ];

    // Casting tipe data
    protected $casts = [
        'ID' => 'integer',
        'BARANG' => 'integer',
        'HARGA_BARANG' => 'integer',
        'JUMLAH' => 'decimal:2',
        'MARGIN' => 'integer',
        'PPN' => 'integer',
        'STATUS' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    // Relasi ke Penjualan (parent)
    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'PENJUALAN_ID', 'NOMOR');
    }



}
