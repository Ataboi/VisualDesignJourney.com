<?php

namespace App\Filament\Resources\FontPairings\Pages;

use App\Filament\Resources\FontPairings\FontPairingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFontPairings extends ListRecords
{
    protected static string $resource = FontPairingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
