<?php

// namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens; // (Mungkin ada di Laravel 8, tidak apa-apa)

// class User extends Authenticatable
// {
//     use HasApiTokens, HasFactory, Notifiable;

//     /**
//      * Kolom yang bisa diisi secara massal (mass assignable).
//      * Ini penting untuk Seeder kita.
//      */
//     protected $fillable = [
//         'nama',
//         'username',
//         'nip',
//         'password',
//         'role_id'
//     ];

//     /**
//      * Kolom yang harus disembunyikan saat di-serialize (misal, diubah ke JSON).
//      */
//     protected $hidden = [
//         'password',
//         'remember_token',
//     ];

//     /**
//      * Tipe data cast (konversi tipe data otomatis).
//      */
//     protected $casts = [
//         'email_verified_at' => 'datetime',
//     ];
// }


// Test sesi kasir
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nama',
        'username',
        'nip',
        'password',
        'role_id', // tambahkan di sini
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relasi ke role (jika ada model Role)
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Jika ingin relasi ke sesi yang dibuka / ditutup oleh user
    public function kasirSesiDibuka()
    {
        return $this->hasMany(KasirSesi::class, 'dibuka_oleh_user_id');
    }

    public function kasirSesiDitutup()
    {
        return $this->hasMany(KasirSesi::class, 'ditutup_oleh_user_id');
    }
}
