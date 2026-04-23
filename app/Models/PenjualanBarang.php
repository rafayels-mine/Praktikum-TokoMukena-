<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanBarang extends Model
{
    use HasFactory;

     protected $table = 'penjualan_barang';
    protected $fillable = ['penjualan_id', 'barang_id', 'harga_beli', 'harga_jual', 'jml', 'tgl'];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }
}

