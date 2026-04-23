<table class="table-auto w-full border-collapse border border-gray-300">
    <thead>
        <tr class="bg-gray-100">
            <th class="border border-gray-300 px-4 py-2 text-left">No Faktur Beli</th>
            <th class="border border-gray-300 px-4 py-2 text-left">Tanggal Transaksi</th>
            <th class="border border-gray-300 px-4 py-2 text-right">Total Belanja</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pembelians as $pembelian)
            <tr>
                <td class="border border-gray-300 px-4 py-2">{{ $pembelian->no_faktur_beli }}</td>
                <td class="border border-gray-300 px-4 py-2">{{ \Carbon\Carbon::parse($pembelian->tgl_pembelian)->format('d/m/Y') }}</td>
                <td class="border border-gray-300 px-4 py-2 text-right">
                    Rp{{ number_format($pembelian->total_bayar, 0, ',', '.') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                    Belum ada data pembelian.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>