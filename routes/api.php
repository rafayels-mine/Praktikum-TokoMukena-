<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// tambahan untuk routes callback agar Midtrans bisa mengirim data tanpa terhalang token CSRF.
// use App\Http\Controllers\CobaMidtransController;
// Route::post('/midtrans-callback', [CobaMidtransController::class, 'handleCallback']);

// routes/api.php untuk mengolah keranjang
Route::post('/midtrans-callback', [App\Http\Controllers\KeranjangController::class, 'handleCallback']);
