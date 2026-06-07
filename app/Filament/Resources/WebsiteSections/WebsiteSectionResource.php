<?php

namespace App\Filament\Resources\WebsiteSections;

use App\Filament\Resources\WebsiteSections\Pages\CreateWebsiteSection;
use App\Filament\Resources\WebsiteSections\Pages\EditWebsiteSection;
use App\Filament\Resources\WebsiteSections\Pages\ListWebsiteSections;
use App\Filament\Resources\WebsiteSections\Schemas\WebsiteSectionForm;
use App\Filament\Resources\WebsiteSections\Tables\WebsiteSectionsTable;
use App\Models\WebsiteSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebsiteSectionResource extends Resource
{
    protected static ?string $model = WebsiteSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WebsiteSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebsiteSectionsTable::configure($table);
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
            'index' => ListWebsiteSections::route('/'),
            'create' => CreateWebsiteSection::route('/create'),
            'edit' => EditWebsiteSection::route('/{record}/edit'),
        ];
    }
}
