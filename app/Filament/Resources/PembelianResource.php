<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembelianResource\Pages;
use App\Models\Pembelian;
use App\Models\Vendor;
use App\Models\Barang;
use App\Models\PembelianBarang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

// Filament Components
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Get;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Pembelian Barang';

    protected static ?string $navigationGroup = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // STEP 1: INFORMASI VENDOR & FAKTUR
                    Wizard\Step::make('Data Vendor')
                        ->schema([
                            Forms\Components\Section::make('no_faktur_pembelian')
                                ->icon('heroicon-m-clipboard-document-check')
                                ->schema([ 
                                    TextInput::make('no_faktur_pembelian')
                                        ->default(fn () => Pembelian::getKodeFakturBeli())
                                        ->label('Nomor Faktur Beli')
                                        ->required()
                                        ->readonly(),
                                    
                                    DateTimePicker::make('tgl')
                                        ->label('Tanggal Beli')
                                        ->default(now())
                                        ->required(),

                                    Select::make('vendor_id')
                                        ->label('Vendor / Supplier')
                                        ->options(Vendor::pluck('nama_vendor', 'id')->toArray())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->placeholder('Pilih Vendor'),

                                    TextInput::make('total_bayar')
                                        ->label('Total Tagihan')
                                        ->default(0)
                                        ->numeric()
                                        ->hidden() // Tersembunyi, diupdate via action
                                        ->dehydrated(),
                                ])
                                ->columns(3),
                        ]),

                    // STEP 2: INPUT BARANG DATANG
                    Wizard\Step::make('Item Barang')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('pembelianBarang') // Pastikan relasi ini ada di model Pembelian
                            ->schema([
                                Select::make('barang_id')
                                    ->label('Barang')
                                    ->options(Barang::pluck('nama_barang', 'id')->toArray())
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $barang = Barang::find($state);
                                        // Set harga beli terakhir dari master barang sebagai default
                                        $set('harga_beli', $barang ? $barang->harga_barang : 0);
                                    }),

                                TextInput::make('harga_beli')
                                    ->label('Harga Satuan')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Rp'),

                                TextInput::make('jml')
                                    ->label('Jumlah Masuk')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->reactive(),

                                DatePicker::make('tgl')
                                    ->label('Tgl. Diterima')
                                    ->default(today())
                                    ->required(),
                            ])
                            ->columns(4)
                            ->addActionLabel('Tambah Barang Masuk')
                            ->minItems(1),

                        // TOMBOL PROSES (Update Stok & Total)
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('Proses Masuk Barang')
                                ->action(function ($get, $set) {
                                    // 1. Simpan/Update Header Pembelian
                                    $pembelian = Pembelian::updateOrCreate(
                                        ['no_faktur_pembelian' => $get('no_faktur_pembelian')],
                                        [
                                            'tgl' => $get('tgl'),
                                            'vendor_id' => $get('vendor_id'),
                                            'total_bayar' => 0, // Sementara
                                            'status' => 'pesan',
                                        ]
                                    );

                                    // 2. Simpan Detail & Update Stok
                                    foreach ($get('items') as $item) {
                                        PembelianBarang::updateOrCreate(
                                            [
                                                'pembelian_id' => $pembelian->id,
                                                'barang_id' => $item['barang_id']
                                            ],
                                            [
                                                'harga_beli' => $item['harga_beli'],
                                                'jml' => $item['jml'],
                                                'tgl' => $item['tgl'],
                                            ]
                                        );

                                        // LOGIKA AKUNTANSI: Pembelian = Stok Bertambah
                                        $barang = Barang::find($item['barang_id']);
                                        if ($barang) {
                                            $barang->increment('stok', $item['jml']);
                                            // Opsional: Update harga master barang ke harga beli terbaru
                                            $barang->update(['harga_barang' => $item['harga_beli']]);
                                        }
                                    }

                                    // 3. Hitung Total Akhir
                                    $totalGlobal = PembelianBarang::where('pembelian_id', $pembelian->id)
                                        ->sum(DB::raw('harga_beli * jml'));

                                    $pembelian->update(['total_bayar' => $totalGlobal]);
                                    
                                    // Notifikasi sukses bisa ditambahkan di sini
                                })
                                ->label('Konfirmasi Stok Masuk')
                                ->color('success')
                                ->icon('heroicon-m-check-circle'),
                        ]),
                    ]),

                    // STEP 3: RINGKASAN
                    Wizard\Step::make('Selesai')
                        ->schema([
                            Placeholder::make('Info')
                                ->content('Barang yang diproses otomatis menambah stok di gudang. Silahkan cek menu Barang untuk memastikan.'),
                        ]),
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_faktur_pembelian')->label('No Faktur')->searchable(),
                TextColumn::make('vendor.nama_vendor')
                    ->label('Vendor/Supplier')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_bayar')
                    ->label('Total Belanja')
                    ->money('IDR') // Menggunakan format mata uang bawaan Filament
                    ->alignment('end'),
                TextColumn::make('tgl')
                    ->label('Tanggal Transaksi')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bayar', 'lunas' => 'success',
                        'pesan' => 'warning',
                        default => 'secondary',
                    }),
            ])
            ->filters([
                SelectFilter::make('vendor_id')
                    ->label('Filter Vendor')
                    ->relationship('vendor', 'nama_vendor')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
        ->before(function (Pembelian $record) {
            foreach ($record->pembelianBarang as $item) {
                $barang = Barang::find($item->barang_id);
                if ($barang) {
                    $barang->decrement('stok', $item->jml);
                }
            }
        }),
    ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            foreach ($records as $record) {
                                foreach ($record->pembelianBarang as $item) {
                                    $barang = Barang::find($item->barang_id);
                                    if ($barang) {
                                        $barang->decrement('stok', $item->jml);
                                    }
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembelians::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'edit' => Pages\EditPembelian::route('/{record}/edit'),
        ];
    }
}