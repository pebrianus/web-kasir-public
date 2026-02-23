<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasirPenjualanDetail extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi kembali ke tabel Head
    public function head()
    {
        return $this->belongsTo(KasirPenjualanHead::class, 'kasir_penjualan_head_id');
    }
}
