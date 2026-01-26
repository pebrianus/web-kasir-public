<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;
    protected $connection = 'simgos_master';
    protected $table = 'pegawai';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    public static function selectNamaLengkap($alias = 'nama_lengkap')
    {
        return DB::raw("
        TRIM(
            CONCAT(
                IFNULL(GELAR_DEPAN, ''),
                IF(GELAR_DEPAN IS NULL OR GELAR_DEPAN = '', '', '. '),

                TRIM(NAMA),

                IF(GELAR_BELAKANG IS NULL OR GELAR_BELAKANG = '', '', ', '),
                IFNULL(GELAR_BELAKANG, '')
            )
        ) AS {$alias}
    ");
    }


}
