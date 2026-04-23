<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContohFormResource\Pages;
use App\Filament\Resources\ContohFormResource\RelationManagers;
use App\Models\ContohForm;
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

class ContohFormResource extends Resource
{
    protected static ?string $model = ContohForm::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
         return $form
            ->schema([
                TextInput::make('nama')
                    ->label('Nama')
                    ->required(),

                Radio::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'Laki-laki' => 'Laki-laki',
                        'Perempuan' => 'Perempuan',
                    ])
                    ->required(),

                Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->maxLength(500)
                    ->required(),

                Select::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'Reguler' => 'Reguler',
                        'VIP' => 'VIP',
                        'VVIP' => 'VVIP',
                    ])
                    ->required(),

                DatePicker::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->required(),

                FileUpload::make('gambar')
                    ->label('Gambar')
                    ->image()
                    ->directory('images')
                    ->required(),

                FileUpload::make('dokumen')
                    ->label('Dokumen')
                    ->directory('documents')
                    ->columnSpan(2)
                    ->required(),

                Toggle::make('is_admin')
                    ->label('Admin?')
                    ->inline(false)
                    ->columnSpan(2)
                    ->required(),

                RichEditor::make('content')
                ->columnSpan(2)
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                ->label('Nama')
                ->searchable()
                ->sortable(),

                TextColumn::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->sortable(),

                BadgeColumn::make('kategori')
                    ->label('Kategori')
                    ->colors([
                        'Reguler' => 'gray',
                        'VIP' => 'yellow',
                        'VVIP' => 'red',
                    ]),

                TextColumn::make('tanggal_lahir')
                    ->label('Tanggal Lahir')
                    ->sortable(),

                TextColumn::make('deskripsi')
                    ->label('Deskripsi')
                    ->sortable(),

                ImageColumn::make('gambar') // Menampilkan gambar di tabel
                    ->label('Gambar')
                    ->size(50), // Menyesuaikan ukuran thumbnail
                
                TextColumn::make('dokumen')
                    ->label('Dokumen')
                    ->url(fn($record) => asset('storage/' . $record->file_path), true)
                    ->formatStateUsing(fn($state) => $state 
                        ? '<a href="' . asset('storage/' . $state) . '" target="_blank"><i class="fas fa-file-pdf"></i> 📄 </a>' 
                        : 'Tidak Ada File')
                    ->html(), // Pastikan menggunakan html() agar bisa merender HTML
                    // Buka file saat diklik

                IconColumn::make('is_admin')
                    ->label('Admin?')
                    ->boolean(),

                TextColumn::make('content')
                    ->label('content')
                    ->limit(50),
                    // ->tooltip(fn($record) => $record->content),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContohForms::route('/'),
            'create' => Pages\CreateContohForm::route('/create'),
            'edit' => Pages\EditContohForm::route('/{record}/edit'),
        ];
    }
}
