<?php

namespace App\Filament\Resources\ColorPalettes\Pages;

use App\Filament\Resources\ColorPalettes\ColorPaletteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditColorPalette extends EditRecord
{
    protected static string $resource = ColorPaletteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
