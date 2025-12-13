<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';
    protected $primaryKey = 'ID';
    public $timestamps = false; // karena tabel tidak punya created_at / updated_at

    protected $fillable = [
        'NAMA',
        'KATEGORI',
        'SATUAN',
        'MERK',
        'PENYEDIA',
        'GENERIK',
        'JENIS_GENERIK',
        'FORMULARIUM',
        'STOK',
        'HARGA_BELI',
        'PPN',
        'HARGA_JUAL',
        'MASA_BERLAKU',
        'JENIS_PENGGUNAAN_OBAT',
        'KLAIM_TERPISAH',
        'TANGGAL',
        'OLEH',
        'STATUS',
        'KLP_PSEDIA',
        'KODE_PSEDIA',
    ];

    // Casting tipe data ke Laravel object otomatis
    protected $casts = [
        'SATUAN' => 'integer',
        'MERK' => 'integer',
        'PENYEDIA' => 'integer',
        'GENERIK' => 'integer',
        'JENIS_GENERIK' => 'integer',
        'FORMULARIUM' => 'integer',
        'STOK' => 'integer',
        'HARGA_BELI' => 'decimal:2',
        'PPN' => 'decimal:2',
        'HARGA_JUAL' => 'decimal:2',
        'MASA_BERLAKU' => 'date',
        'JENIS_PENGGUNAAN_OBAT' => 'integer',
        'KLAIM_TERPISAH' => 'integer',
        'TANGGAL' => 'datetime',
        'OLEH' => 'integer',
        'STATUS' => 'integer',
    ];
}
