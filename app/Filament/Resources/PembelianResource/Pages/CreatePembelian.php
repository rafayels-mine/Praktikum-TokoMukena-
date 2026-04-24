<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

// Model
use App\Models\Pembelian;
use App\Models\PembelianBarang;
use App\Models\PembayaranPembelian; 
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
            // 1. Tombol Simpan Standar (Hanya muncul jika statusnya 'pesan'/Utang)
            $this->getCreateFormAction()
                ->label('Draft')
                ->color('warning')
                ->hidden(fn () => ($this->data['status'] ?? '') === 'bayar'),

            // 2. Tombol kustom Bayar & Lunasi (Hanya muncul jika statusnya 'bayar')
            Actions\Action::make('lunasi_ke_vendor')
                ->label('Bayar & Lunasi Sekarang')
                ->color('success')
                ->icon('heroicon-m-banknotes')
                ->action(fn () => $this->simpanPembayaranVendor())
                ->hidden(fn () => ($this->data['status'] ?? '') !== 'bayar')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pembayaran Vendor')
                ->modalDescription('Apakah Anda yakin ingin mencatat pelunasan untuk pembelian ini?')
                ->modalButton('Ya, Lunasi Sekarang'),

            // 3. Tombol cancel
            $this->getCancelFormAction(),
        ];
    }

    /**
     * Logika untuk menyimpan data ke tabel PembayaranPembelian
     */
    protected function simpanPembayaranVendor()
    {
        // Jalankan proses create record terlebih dahulu agar ID tersedia
        $this->create();

        // Ambil record yang baru saja dibuat
        $pembelian = $this->record;

        if (!$pembelian) {
            Notification::make()
                ->title('Gagal!')
                ->body('Data pembelian tidak ditemukan.')
                ->danger()
                ->send();
            return;
        }

        // 1. Simpan ke tabel PembayaranPembelian (Uang Keluar)
        PembayaranPembelian::create([
            'pembelian_id'      => $pembelian->id,
            'tgl_bayar'         => now(),
            'jenis_pembayaran'  => 'transfer', 
            'transaction_time'  => now(),
            'gross_amount'      => $pembelian->total_bayar, 
            'order_id'          => $pembelian->no_faktur_pembelian, 
        ]);

        // 2. Pastikan status pembelian menjadi 'bayar' (Lunas)
        $pembelian->update([
            'status' => 'bayar', 
        ]);

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