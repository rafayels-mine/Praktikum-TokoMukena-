<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// tambahan
use Illuminate\Support\Facades\DB;

class Kamar extends Model
{
    use HasFactory;

    protected $table = 'kamar'; // Nama tabel eksplisit

    protected $guarded = [];

    public static function getKodeKamar()
    {
        // query kode kamar terakhir
        $sql = "SELECT IFNULL(MAX(RIGHT(no_kamar,3)), 'KMR-000') as no_kamar
                FROM kamar";

        $nokamar = DB::select($sql);

        // baca hasil query
        foreach ($nokamar as $nk) {
            $kd = $nk->no_kamar;
        }

        // mengambil tiga digit terakhir lalu tambah 1
        $noawal = substr($kd,-3);
        $noakhir = $noawal+1; //menambahkan 1, hasilnya adalah integer cth 1
        $noakhir = 'KMR'.str_pad($noakhir,3,"0",STR_PAD_LEFT); //menyambung dengan string PR-001
        return $noakhir;

    }
}
