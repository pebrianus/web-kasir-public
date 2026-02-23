<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasirPenjualanHead extends Model
{
    use HasFactory;

    protected $guarded = ['id']; // Membolehkan mass-assignment untuk semua kolom kecuali ID

    // Relasi One-to-Many ke tabel detail
    public function details()
    {
        return $this->hasMany(KasirPenjualanDetail::class, 'kasir_penjualan_head_id');
    }
}
