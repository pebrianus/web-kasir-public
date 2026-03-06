<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasirTagihanPiutang extends Model
{
    protected $table = 'kasir_tagihan_piutang';

    protected $fillable = [
        'kasir_tagihan_head_id',
        'simgos_tagihan_id',
        'simgos_norm',
        'nama_pasien',
        'nama_asuransi',
        'total_tagihan_asuransi',
        'nominal_piutang',
        'nominal_terbayar',
        'nominal_sisa',
        'status',
        'tanggal_jatuh_tempo',
        'tanggal_lunas',
        'user_id',
        'kasir_sesi_id',
        'keterangan',
    ];

    protected $casts = [
        'total_tagihan_asuransi' => 'decimal:2',
        'nominal_piutang'        => 'decimal:2',
        'nominal_terbayar'       => 'decimal:2',
        'nominal_sisa'           => 'decimal:2',
        'tanggal_jatuh_tempo'    => 'date',
        'tanggal_lunas'          => 'date',
    ];

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------

    public function tagihanHead()
    {
        return $this->belongsTo(KasirTagihanHead::class, 'kasir_tagihan_head_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kasirSesi()
    {
        return $this->belongsTo(KasirSesi::class, 'kasir_sesi_id');
    }

    // -----------------------------------------------
    // Scopes
    // -----------------------------------------------

    public function scopeOutstanding($query)
    {
        return $query->where('status', 'outstanding');
    }

    public function scopeSebagian($query)
    {
        return $query->where('status', 'sebagian');
    }

    public function scopeLunas($query)
    {
        return $query->where('status', 'lunas');
    }

    public function scopeBelumLunas($query)
    {
        return $query->whereIn('status', ['outstanding', 'sebagian']);
    }

    public function scopeByAsuransi($query, $namaAsuransi)
    {
        return $query->where('nama_asuransi', $namaAsuransi);
    }

    public function scopeJatuhTempo($query)
    {
        return $query->whereNotNull('tanggal_jatuh_tempo')
                     ->where('tanggal_jatuh_tempo', '<=', now())
                     ->belumLunas();
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    /**
     * Tambah nominal terbayar dan update status otomatis.
     */
    public function tambahPembayaran(float $nominal): void
    {
        $this->nominal_terbayar += $nominal;
        $this->nominal_sisa      = max(0, $this->nominal_piutang - $this->nominal_terbayar);

        if ($this->nominal_sisa <= 0) {
            $this->status        = 'lunas';
            $this->tanggal_lunas = now()->toDateString();
        } else {
            $this->status = 'sebagian';
        }

        $this->save();
    }

    /**
     * Cek apakah piutang sudah jatuh tempo.
     */
    public function isJatuhTempo(): bool
    {
        return $this->tanggal_jatuh_tempo
            && $this->tanggal_jatuh_tempo->isPast()
            && $this->status !== 'lunas';
    }

    /**
     * Persentase pelunasan.
     */
    public function persentaseTerbayar(): float
    {
        if ($this->nominal_piutang <= 0) return 0;
        return round(($this->nominal_terbayar / $this->nominal_piutang) * 100, 2);
    }
}
