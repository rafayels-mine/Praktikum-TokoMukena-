<?php

namespace App\Filament\Resources\ContohFormResource\Pages;

use App\Filament\Resources\ContohFormResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContohForms extends ListRecords
{
    protected static string $resource = ContohFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
