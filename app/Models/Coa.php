<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coa extends Model
{
    use HasFactory;

 // karena kita merubah tabelnya dari coas menjadi coa
    protected $table = 'coa';

  // seluruh kolom dapat dimodifikasi
    protected $guarded = [];
}
