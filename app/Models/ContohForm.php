<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContohForm extends Model
{
    use HasFactory;

    protected $table = 'contoh_form'; // Nama tabel eksplisit

    protected $guarded = [];
}
