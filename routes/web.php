<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('login');
});

// login customer
Route::get('/depan', [App\Http\Controllers\KeranjangController::class, 'daftarbarang'])->middleware('customer')->name('depan');
Route::get('/login', [App\Http\Controllers\AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login']);
Route::get('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// contoh route yang mengarah ke konten statis
Route::get('/selamat', function () {
    return view('selamat',['nama'=>'Farel Prayoga']);
});

// contoh route yang mengarah ke konten statis
Route::get('/utama', function () {
    return view('layout',['nama'=>'Farel Prayoga', 'title'=>'Selamat Datang']);
});

// contoh route tanpa view, hanya controller
Route::get('/contoh1', [App\Http\Controllers\Contoh1Controller::class,'show']);

// contoh route tanpa view, hanya controller dengan membagi layout 
Route::get('/contoh2', [App\Http\Controllers\Contoh2Controller::class,'show']);

// contoh route coa
Route::get('/coa', [App\Http\Controllers\CoaController::class,'index']);

// untuk contoh perusahaan
use App\Http\Controllers\PerusahaanController;
Route::resource('perusahaan', PerusahaanController::class);
Route::get('/perusahaan/destroy/{id}', [PerusahaanController::class,'destroy']);


// untuk ubah password
Route::get('/ubahpassword', [App\Http\Controllers\AuthController::class, 'ubahpassword'])
    ->middleware('customer')
    ->name('ubahpassword');
Route::post('/prosesubahpassword', [App\Http\Controllers\AuthController::class, 'prosesubahpassword'])
    ->middleware('customer')
;
// prosesubahpassword                                                                                                                                                                                                                                                                                      