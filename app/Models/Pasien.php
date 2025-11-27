<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    use HasFactory;

    /**
     * 1. Beritahu Laravel untuk menggunakan koneksi 'simgos_master'
     * (yang kita atur di config/database.php)
     */
    protected $connection = 'simgos_master';

    /**
     * 2. Beritahu Laravel nama tabel yang benar
     */
    protected $table = 'pasien';

    /**
     * 3. Beritahu Laravel Primary Key-nya
     * (Berdasarkan screenshot Anda, sepertinya 'NORM')
     */
    protected $primaryKey = 'NORM';

    /**
     * 4. Beritahu Laravel bahwa tabel ini tidak menggunakan
     * primary key auto-increment (karena NORM mungkin di-input manual)
     */
    public $incrementing = false;

    /**
     * 5. Beritahu Laravel bahwa tabel ini tidak punya
     * kolom 'created_at' dan 'updated_at'
     */
    public $timestamps = false;
}
