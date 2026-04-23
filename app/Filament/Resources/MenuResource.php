<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Filament\Resources\MenuResource\RelationManagers;
use App\Models\Menu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// tambahan untuk komponen input form
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Radio;
// tambahan untuk komponen kolom
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Grid;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
{
    return $form
        ->schema([
            TextInput::make('kode_menu')
                    ->default(fn () => Menu::getKodeMenu()) // Ambil default dari method getKodeMenu
                    ->label('Kode Menu')
                    ->required()
                    ->readonly(),

            TextInput::make('nama_menu')
                ->required()
                ->label('Nama Menu'),

            TextInput::make('harga')
                ->required()
                ->numeric()
                ->prefix('Rp')
                ->label('Harga'),

            Select::make('kategori')
                ->options([
                    'Makanan' => 'Makanan',
                    'Minuman' => 'Minuman',
                ])
                ->required()
                ->label('Kategori'),

            Select::make('status')
                ->options([
                    'Tersedia' => 'Tersedia',
                    'Tidak Tersedia' => 'Tidak Tersedia',
                ])
                ->default('Tersedia')
                ->required()
                ->label('Status'),

            FileUpload::make('gambar')
                ->image()
                ->directory('menu')
                ->label('Gambar'),

            TextInput::make('deskripsi')
                ->label('Deskripsi')
                ->nullable(),

            Toggle::make('is_admin')
                    ->label('Admin?')
                    ->inline(false)
                    ->columnSpan(2)
                    ->required(),
        ]);
}
    }

    public static function table(Table $table): Table
    {
    return $table
        ->columns([
            TextColumn::make('kode_menu')
                    ->searchable(),

            TextColumn::make('nama_menu')
                ->searchable()
                ->sortable()
                ->label('Nama Menu'),

            BadgeColumn::make('kategori')
                    ->label('Kategori')
                    ->colors([
                        'Makanan' => 'green',
                        'Minuman' => 'blue',
                    ]),

            TextColumn::make('harga')
                ->money('IDR')
                ->sortable(),

            BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'Tersedia' => 'green',
                        'Tidak Tersedia' => 'red',
                    ]),

            ImageColumn::make('gambar')
                ->label('Gambar')
                ->size(35),

            IconColumn::make('is_admin')
                    ->label('Admin?')
                    ->boolean(),

            
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
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
