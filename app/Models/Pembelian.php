<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//tambahan
use Illuminate\Support\Facades\DB;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelian'; // Nama tabel eksplisit

    protected $guarded = [];

    /**
     * Logika generate nomor faktur pembelian otomatis
     * Contoh: B-0000001
     */
    public static function getKodeFakturBeli()
    {
        // Query faktur terakhir
        $sql = "SELECT IFNULL(MAX(no_faktur_pembelian), 'B-0000000') as no_faktur 
                FROM pembelian ";
        $kodefaktur = DB::select($sql);

        foreach ($kodefaktur as $kdbeli) {
            $kd = $kdbeli->no_faktur;
        }

        // Ambil angka setelah 'B-'
        $noawal = substr($kd, -7);
        $noakhir = (int)$noawal + 1; 
        
        // Bungkus kembali menjadi format B-0000001
        $noakhir = 'B-' . str_pad($noakhir, 7, "0", STR_PAD_LEFT);
        return $noakhir;
    }

    // Relasi ke tabel Vendor (Penyedia barang)
    public function vendor()
    {
        // Pastikan nama kolom di tabel pembelian adalah 'vendor_id'
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    // Relasi ke tabel detail barang yang dibeli
    public function pembelianBarang()
    {
        return $this->hasMany(PembelianBarang::class, 'pembelian_id');
    }

    // Opsi: Relasi ke tabel pembayaran (untuk melacak pelunasan ke vendor)
    public function pembayaranPembelian()
    {
        return $this->hasMany(PembayaranPembelian::class, 'pembelian_id');
    }

    public function hitungSisaHutang()
{
    $totalTagihan = $this->total_bayar;
    $totalDibayar = $this->pembayaranPembelian()->sum('jumlah_bayar');
    
    return $totalTagihan - $totalDibayar;
}
}