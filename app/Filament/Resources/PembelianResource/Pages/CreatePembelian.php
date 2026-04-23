<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

// Model
use App\Models\Pembelian;
use App\Models\PembelianBarang;
use App\Models\PembayaranPembelian; // Sesuaikan dengan nama model pembayaran pembelianmu
use Filament\Notifications\Notification;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    /**
     * Menambahkan tombol khusus di bagian bawah form create
     */
    protected function getFormActions(): array
    {
        return [
            
            // Tombol kustom untuk langsung proses pelunasan ke Vendor
            Actions\Action::make('lunasi_ke_vendor')
                ->label('Bayar & Lunasi')
                ->color('info')
                ->icon('heroicon-m-banknotes')
                ->action(fn () => $this->simpanPembayaranVendor())
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembayaran Vendor')
                ->modalDescription('Apakah Anda yakin ingin mencatat pelunasan untuk pembelian ini?')
                ->modalButton('Ya, Lunasi Sekarang'),

            // Tombol cancel
            $this->getCancelFormAction(),
        ];
    }

    /**
     * Logika untuk menyimpan data ke tabel pengeluaran_vendor
     */
    protected function simpanPembayaranVendor()
    {
        // Pastikan record utama (Pembelian) sudah tersimpan/ada
        $pembelian = $this->record ?? Pembelian::latest()->first();

        if (!$pembelian) {
            Notification::make()
                ->title('Gagal!')
                ->body('Data pembelian tidak ditemukan.')
                ->danger()
                ->send();
            return;
        }

        // 1. Simpan ke tabel pengeluaran_vendor (Uang Keluar)
       PembayaranPembelian::create([
    'pembelian_id'      => $pembelian->id,
    'tgl_bayar'         => now(),
    'jenis_pembayaran'  => 'transfer', // Atau 'tunai' sesuai kebutuhan
    'transaction_time'  => now(),
    'gross_amount'      => $pembelian->total_bayar, 
    'order_id'          => $pembelian->no_faktur_pembelian, 
    ]);

    $pembelian->update([
            'status' => 'lunas', // Update status pembelian menjadi 'lunas'
        ]);

        // 2. Opsional: Jika kamu punya kolom status di tabel pembelian
        // $pembelian->update(['status' => 'lunas']);

        // 3. Notifikasi Sukses
        Notification::make()
            ->title('Pembayaran Vendor Berhasil Dicatat!')
            ->body('Data pengeluaran telah masuk ke pembukuan.')
            ->success()
            ->send();
            
        // Redirect kembali ke halaman list
        $this->redirect($this->getResource()::getUrl('index'));
    }
}