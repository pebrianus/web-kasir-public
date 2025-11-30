<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasirPembayaran extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kasir_pembayaran';

    // Kolom yang boleh diisi
    protected $fillable = [
        'kasir_tagihan_head_id',
        'user_id',
        'metode_bayar_id',
        'nominal_bayar',
        'kasir_sesi_id',
        'deleted_at'
    ];
}
