<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Contoh2Controller extends Controller
{

 //
    public function show(){
        return view(
                        'layout',
                        [
                            'title' => 'Selamat Datang Di Web Framework',
                            'nama' => 'Di akses dari controller'
                        ]
        );
    }
}
