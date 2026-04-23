<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// UI Components
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Vendor / Supplier';

    protected static ?string $navigationGroup = 'Masterdata';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Kode Vendor otomatis
                TextInput::make('kode_vendor')
                    ->default(fn () => Vendor::getKodeVendor())
                    ->label('Kode Vendor')
                    ->required()
                    ->readonly(),

                // Nama Vendor (Input Manual karena tidak relasi ke User)
                TextInput::make('nama_vendor')
                    ->label('Nama Vendor / Perusahaan')
                    ->required()
                    ->placeholder('Contoh: PT. Maju Jaya Logistik')
                    ->maxLength(255),

                TextInput::make('alamat')
                    ->label('Alamat Kantor')
                    ->required()
                    ->placeholder('Masukkan alamat lengkap vendor'),

                TextInput::make('telepon')
                    ->label('Nomor Telepon / WhatsApp')
                    ->required()
                    ->placeholder('Contoh: 08123456789')
                    ->numeric()
                    ->prefix('+62'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_vendor')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('nama_vendor')
                    ->label('Nama Vendor')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('telepon')
                    ->label('Kontak'),

                TextColumn::make('alamat')
                    ->label('Lokasi')
                    ->limit(30), // Agar tabel tidak terlalu lebar
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}