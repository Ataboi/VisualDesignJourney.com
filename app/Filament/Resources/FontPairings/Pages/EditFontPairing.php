<?php

namespace App\Filament\Resources\FontPairings\Pages;

use App\Filament\Resources\FontPairings\FontPairingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFontPairing extends EditRecord
{
    protected static string $resource = FontPairingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
