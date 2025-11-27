<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class KasirSesi extends Model
// {
//     use HasFactory;

//     protected $table = 'kasir_sesi';

//     // Kolom yang boleh diisi
//     protected $fillable = [
//         'nama_sesi',
//         'waktu_buka',
//         'waktu_tutup',
//         'dibuka_oleh_user_id',
//         'ditutup_oleh_user_id',
//         'total_penerimaan_sistem',
//         'status',
//     ];

//     // Konversi otomatis kolom tanggal
//     protected $casts = [
//         'waktu_buka' => 'datetime',
//         'waktu_tutup' => 'datetime',
//     ];
// }


// <?php
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
    ];

    protected $casts = [
        'waktu_buka' => 'datetime',
        'waktu_tutup' => 'datetime',
    ];

    public function userPembuka()
    {
        return $this->belongsTo(\App\Models\User::class, 'dibuka_oleh_user_id');
    }

    public function userPenutup()
    {
        return $this->belongsTo(\App\Models\User::class, 'ditutup_oleh_user_id');
    }
}
