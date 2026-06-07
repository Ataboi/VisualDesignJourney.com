<?php

namespace App\Filament\Resources\ColorPalettes;

use App\Filament\Resources\ColorPalettes\Pages\CreateColorPalette;
use App\Filament\Resources\ColorPalettes\Pages\EditColorPalette;
use App\Filament\Resources\ColorPalettes\Pages\ListColorPalettes;
use App\Filament\Resources\ColorPalettes\Schemas\ColorPaletteForm;
use App\Filament\Resources\ColorPalettes\Tables\ColorPalettesTable;
use App\Models\ColorPalette;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ColorPaletteResource extends Resource
{
    protected static ?string $model = ColorPalette::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ColorPaletteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ColorPalettesTable::configure($table);
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
            'index' => ListColorPalettes::route('/'),
            'create' => CreateColorPalette::route('/create'),
            'edit' => EditColorPalette::route('/{record}/edit'),
        ];
    }
}
