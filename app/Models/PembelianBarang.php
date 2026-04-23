<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//tambahan
use Illuminate\Support\Facades\DB;

class PembelianBarang extends Model
{
    use HasFactory;

    protected $table = 'pembelian_barang';
    
    // Sesuaikan fillable dengan kolom yang ada di migration pembelian_barang kamu
    protected $guarded = [];

    // Relasi ke tabel header (Pembelian)
    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id');
    }

    // Relasi ke tabel master barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}