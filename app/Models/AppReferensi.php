<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppReferensi extends Model
{
    use HasFactory;

    protected $table = 'app_referensi';
    protected $primaryKey = 'TABEL_ID';
    public $timestamps = false;
}
