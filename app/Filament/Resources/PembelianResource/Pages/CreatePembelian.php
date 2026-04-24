<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Models\Barang;
use App\Models\Pembelian;
use App\Models\PembelianBarang;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    public static function canCreateAnother(): bool
    {
        return false;
    }

    // ✅ Cegah Filament insert ulang — cukup fetch record yang sudah dibuat action button
    protected function handleRecordCreation(array $data): Model
    {
        $existing = Pembelian::where('no_faktur_pembelian', $data['no_faktur_pembelian'])->first();

        if ($existing) {
            // Update status jika sudah ada (dari action button)
            $existing->update([
                'status' => $data['status'] ?? $existing->status,
            ]);
            return $existing;
        }

        // Fallback: jika user tidak klik konfirmasi, tetap bisa create
        return Pembelian::create([
            'no_faktur_pembelian' => $data['no_faktur_pembelian'],
            'tgl'                 => $data['tgl'],
            'vendor_id'           => $data['vendor_id'],
            'status'              => $data['status'] ?? 'pesan',
            'total_bayar'         => 0,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}