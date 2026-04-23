<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

// Tambahan
use App\Http\Controllers\NotificationController;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    /**
     * Hook yang dijalankan tepat setelah data vendor berhasil disimpan
     */
    protected function afterCreate(): void
    {
        // 1. Ambil data vendor yang baru saja disimpan
        $vendor = $this->record;

        // 2. Bersihkan nomor telepon (menghilangkan karakter non-angka)
        // Karena di Resource pakai prefix '+62', regex ini akan menyisakan angka saja
        $nomorWa = preg_replace('/[^0-9]/', '', $vendor->telepon);

        // Pastikan format nomor telepon sesuai kebutuhan Fonnte (misal harus 628...)
        // Jika input awal sudah 62, maka tidak perlu ditambah 0 di depan
        
        // 3. Susun Pesan untuk Vendor
        $pesan = "Halo *{$vendor->nama_vendor}*,\n\n" .
                 "Terima kasih telah bergabung sebagai Mitra Vendor kami.\n" .
                 "Data Anda telah terdaftar dengan rincian:\n" .
                 "Kode Vendor: *{$vendor->kode_vendor}*\n" .
                 "Alamat: {$vendor->alamat}\n\n" .
                 "Informasi mengenai Purchase Order (PO) selanjutnya akan dikirimkan melalui nomor ini atau email *{$vendor->email}*.\n\n" .
                 "Salam,\n" .
                 "Tim Logistik & Operasional";

        // 4. Panggil NotificationController
        try {
            $wa = app(\App\Http\Controllers\NotificationController::class);
            // Sesuaikan pemanggilan method sesuai logic di NotificationController kamu
            // $wa->sendMessage($nomorWa, $pesan); 
        } catch (\Exception $e) {
            // Log error jika pengiriman gagal agar tidak mengganggu flow aplikasi
            \Log::error("Gagal mengirim WA ke Vendor: " . $e->getMessage());
        }
    }
}