<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'role'; // karena nama tabel bukan 'roles'

    protected $fillable = [
        'name',
    ];
}
