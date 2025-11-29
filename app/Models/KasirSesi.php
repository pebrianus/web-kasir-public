<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KasirSesi extends Model
{
    use HasFactory;

    protected $table = 'kasir_sesi';

    protected $fillable = [
        'nama_sesi',
        'waktu_buka',
        'waktu_tutup',
        'dibuka_oleh_user_id',
        'ditutup_oleh_user_id',
        'total_penerimaan_sistem',
        'status',
        'jenis_kasir', // <-- WAJIB ditambah
    ];

    protected $casts = [
        'waktu_buka' => 'datetime',
        'waktu_tutup' => 'datetime',
    ];

    // ============================
    //           RELASI
    // ============================

    // User yang membuka sesi
    public function userPembuka()
    {
        return $this->belongsTo(User::class, 'dibuka_oleh_user_id');
    }

    // User yang menutup sesi
    public function userPenutup()
    {
        return $this->belongsTo(User::class, 'ditutup_oleh_user_id');
    }

    // Relasi ke pembayaran (optional tapi rekomendasi)
    public function pembayaran()
    {
        return $this->hasMany(KasirPembayaran::class, 'kasir_sesi_id');
    }
}
