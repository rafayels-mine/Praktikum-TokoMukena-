<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KamarResource\Pages;
use App\Filament\Resources\KamarResource\RelationManagers;
use App\Models\Kamar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// tambahan
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload; //untuk tipe file

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;

class KamarResource extends Resource
{
    protected static ?string $model = Kamar::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            TextInput::make('no_kamar')
            ->default(fn () => Kamar::getKodeKamar())
            ->label('No Kamar')
            ->required()
            ->readonly(),

        TextInput::make('nama_kamar')
            ->required()
            ->placeholder('Masukkan nama kamar'),

        TextInput::make('lantai_kamar')
            ->numeric()
            ->required()
            ->minValue(1)
            ->placeholder('Masukkan lantai kamar'),

        FileUpload::make('foto_kamar')
            ->directory('foto_kamar')
            ->required(),

        TextInput::make('harga_kamar')
            ->label('harga_kamar')
            ->extraAttributes(['class' => 'text-right']) // Tambahkan kelas CSS untuk rata kanan
            ->required(),

        Select::make('status_kamar')
            ->options([
                'Kosong' => 'Kosong',
                'Terisi' => 'Terisi'
            ])
            ->required()

    ]);
        
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            TextColumn::make('no_kamar')
            ->searchable(),

        TextColumn::make('nama_kamar')
            ->searchable()
            ->sortable(),

        TextColumn::make('lantai_kamar')
            ->sortable(),

        ImageColumn::make('foto_kamar')
            ->label('Foto'),

        TextColumn::make('harga_kamar')
            ->label('Harga Kamar')
            ->formatStateUsing(fn (int|null $state): string => rupiah($state))
            ->extraAttributes(['class' => 'text-right']) // Tambahkan kelas CSS untuk rata
            ->sortable(),

        TextColumn::make('status_kamar')
            ->sortable(),


    ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListKamars::route('/'),
            'create' => Pages\CreateKamar::route('/create'),
            'edit' => Pages\EditKamar::route('/{record}/edit'),
        ];
    }
}
