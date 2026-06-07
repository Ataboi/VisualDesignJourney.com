<?php

namespace App\Filament\Resources\ColorPalettes\Pages;

use App\Filament\Resources\ColorPalettes\ColorPaletteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListColorPalettes extends ListRecords
{
    protected static string $resource = ColorPaletteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
