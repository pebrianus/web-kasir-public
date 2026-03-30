<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasirPiutangPembayaran extends Model
{
    protected $table = 'kasir_piutang_pembayaran';

    protected $fillable = [
        'kasir_tagihan_piutang_id',
        'nominal_bayar',
        'nominal_sisa_sebelum',
        'nominal_sisa_sesudah',
        'tanggal_bayar',
        'status_sebelum',
        'status_sesudah',
        'keterangan',
        'user_id',
    ];

    protected $casts = [
        'nominal_bayar'        => 'decimal:2',
        'nominal_sisa_sebelum' => 'decimal:2',
        'nominal_sisa_sesudah' => 'decimal:2',
        'tanggal_bayar'        => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relasi
    // -------------------------------------------------------------------------

    /**
     * Piutang induk milik pembayaran ini.
     */
    public function piutang(): BelongsTo
    {
        return $this->belongsTo(KasirTagihanPiutang::class, 'kasir_tagihan_piutang_id');
    }

    /**
     * User yang melakukan pembayaran.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -------------------------------------------------------------------------
    // Accessor
    // -------------------------------------------------------------------------

    /**
     * Apakah pembayaran ini yang melunasi piutang.
     */
    public function getIsLunasAttribute(): bool
    {
        return $this->status_sesudah === 'lunas';
    }
}
