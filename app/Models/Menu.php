<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// tambahan
use Illuminate\Support\Facades\DB;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu'; // Nama tabel eksplisit

    protected $guarded = [];

    public static function getKodeMenu()
    {
        // query kode perusahaan
        $sql = "SELECT IFNULL(MAX(kode_menu), 'MKN000') as kode_menu 
                FROM menu ";
        $kodemenu = DB::select($sql);

        // cacah hasilnya
        foreach ($kodemenu as $kd) {
            $kd = $kd->kode_menu;
        }
        // Mengambil substring tiga digit akhir dari string PR-000
        $noawal = substr($kd,-3);
        $noakhir = $noawal+1; //menambahkan 1, hasilnya adalah integer cth 1
        $noakhir = 'MKN'.str_pad($noakhir,3,"0",STR_PAD_LEFT); //menyambung dengan string PR-001
        return $noakhir;

    }

    // Dengan mutator ini, setiap kali data harga_barang dikirim ke database, koma akan otomatis dihapus.
    public function setHargaAttribute($value)
    {
        // Hapus koma (,) dari nilai sebelum menyimpannya ke database
        $this->attributes['harga'] = str_replace('.', '', $value);
    }
}
