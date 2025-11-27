<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasirTagihanHead extends Model
{
    use HasFactory;

    // Nama tabel kita
    protected $table = 'kasir_tagihan_head';

    // Kolom yang boleh diisi saat 'create'
    protected $fillable = [
        'simgos_tagihan_id',
        'simgos_norm',
        'simgos_nopen', // (Anda belum mengirim ini, kita tambahkan nanti)
        'nama_pasien',
        'nama_ruangan',
        'nama_dokter',
        'nama_asuransi',
        'simgos_tanggal_tagihan',
        'total_asli_simgos',
        'diskon_simgos',
        'total_bayar_pasien',
        'total_bayar_asuransi',
        'status_kasir',
    ];
}
