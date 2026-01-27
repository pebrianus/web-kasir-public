<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagihanPendaftaran extends Model
{
    use HasFactory;

    protected $connection = 'simgos_pembayaran';
    protected $table = 'tagihan_pendaftaran';
    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $fillable = [
        'TAGIHAN',
        'PENDAFTARAN',
        'REF',
        'UTAMA',
        'STATUS',
    ];

    protected $casts = [
        'ID'     => 'integer',
        'UTAMA'  => 'integer',
        'STATUS' => 'integer',
    ];
}
