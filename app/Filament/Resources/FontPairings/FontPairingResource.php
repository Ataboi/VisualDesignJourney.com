<?php

namespace App\Filament\Resources\FontPairings;

use App\Filament\Resources\FontPairings\Pages\CreateFontPairing;
use App\Filament\Resources\FontPairings\Pages\EditFontPairing;
use App\Filament\Resources\FontPairings\Pages\ListFontPairings;
use App\Filament\Resources\FontPairings\Schemas\FontPairingForm;
use App\Filament\Resources\FontPairings\Tables\FontPairingsTable;
use App\Models\FontPairing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FontPairingResource extends Resource
{
    protected static ?string $model = FontPairing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FontPairingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FontPairingsTable::configure($table);
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
            'index' => ListFontPairings::route('/'),
            'create' => CreateFontPairing::route('/create'),
            'edit' => EditFontPairing::route('/{record}/edit'),
        ];
    }
}
