<?php

namespace App\Filament\Resources\DesignPrompts;

use App\Filament\Resources\DesignPrompts\Pages\CreateDesignPrompt;
use App\Filament\Resources\DesignPrompts\Pages\EditDesignPrompt;
use App\Filament\Resources\DesignPrompts\Pages\ListDesignPrompts;
use App\Filament\Resources\DesignPrompts\Schemas\DesignPromptForm;
use App\Filament\Resources\DesignPrompts\Tables\DesignPromptsTable;
use App\Models\DesignPrompt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DesignPromptResource extends Resource
{
    protected static ?string $model = DesignPrompt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DesignPromptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesignPromptsTable::configure($table);
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
            'index' => ListDesignPrompts::route('/'),
            'create' => CreateDesignPrompt::route('/create'),
            'edit' => EditDesignPrompt::route('/{record}/edit'),
        ];
    }
}
