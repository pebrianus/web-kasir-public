<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasirTagihanDetail extends Model
{
    use HasFactory;

    protected $table = 'kasir_tagihan_detail';

    // Kita tidak pakai 'created_at'/'updated_at' di tabel detail
    public $timestamps = false;

    // Kolom yang boleh diisi
    protected $fillable = [
        'kasir_tagihan_head_id',
        'simgos_ref_id',
        'simgos_jenis_tarif',
        'deskripsi_item',
        'qty',
        'harga_satuan',
        'subtotal',
        'diskon_item',
        'nominal_ditanggung_asuransi',
        'nominal_ditanggung_pasien',
    ];
}
