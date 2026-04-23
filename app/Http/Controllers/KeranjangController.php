<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// tambahan untuk akses ke model barang
use App\Models\Barang; //untuk akses kelas model barang

class KeranjangController extends Controller
{
    // method daftar barang
    public function daftarbarang()
    {
        // awal
        // return view( 'galeri');

        // ambil data barang
        $barang = Barang::all();
        // kirim ke halaman view
        return view('galeri2',
                        [ 
                            'barang'=>$barang,
                        ]
                    ); 

    }
}