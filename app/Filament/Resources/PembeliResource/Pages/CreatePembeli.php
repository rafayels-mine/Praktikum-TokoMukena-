<?php

namespace App\Filament\Resources\PembeliResource\Pages;

use App\Filament\Resources\PembeliResource;
// tambahan
use App\Http\Controllers\NotificationController;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePembeli extends CreateRecord
{
    protected static string $resource = PembeliResource::class;

    /**
     * Hook yang dijalankan tepat setelah data berhasil disimpan ke database
     */
    protected function afterCreate(): void
    {
        // 1. Ambil data yang baru saja disimpan
        $pembeli = $this->record;
        
        // 2. Ambil data User terkait (untuk mendapatkan email)
        $user = $pembeli->user;

        // 3. Siapkan nomor telepon
        // Jika input telepon di form diawali '0', Fonnte biasanya butuh format 62 atau 08
        // Karena di Resource kamu pakai prefix '+62', pastikan formatnya sesuai
        $nomorWa = preg_replace('/[^0-9]/', '', $pembeli->telepon);
        $passwordTeks = "password123"; // Ganti sesuai logika password kamu

        // 4. Susun Pesan
        $pesan = "Halo *{$pembeli->nama_pembeli}*,\n\n" .
                 "Registrasi telah berhasil.\n" .
                 "Email: {$user->email}\n" .
                 "Password: {$passwordTeks}\n\n" .
                 "Segera lakukan pergantian password demi keamanan akun Anda.";

        // 3. Panggil WhatsAppController
        // Kita gunakan instansiasi objek karena ini di dalam class
         $wa = app(\App\Http\Controllers\NotificationController::class);
        //$wa->sendMessage('0'.$nomorWa, $pesan);
    }
}