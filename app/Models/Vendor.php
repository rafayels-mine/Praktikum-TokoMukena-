<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// tambahan
use Illuminate\Support\Facades\DB;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendor'; // Sesuai nama di migration

    protected $guarded = [];

    public static function getKodeVendor()
    {
        // Query kode vendor terakhir, default V-00000
        $sql = "SELECT IFNULL(MAX(kode_vendor), 'V-00000') as kode_vendor 
                FROM vendor";
        $kodevendor = DB::select($sql);

        // Cacah hasilnya
        foreach ($kodevendor as $kdvnd) {
            $kd = $kdvnd->kode_vendor;
        }

        // Mengambil 5 digit akhir
        $noawal = substr($kd, -5);
        $noakhir = $noawal + 1; 
        
        // Format menjadi V-00001
        $noakhir = 'V-' . str_pad($noakhir, 5, "0", STR_PAD_LEFT);
        return $noakhir;
    }

    // Relasi ke tabel pembelian (Vendor punya banyak transaksi pembelian)
    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'vendor_id');
    }
}