<?php

namespace App\Filament\Resources\VdjBlogCategories;

use App\Filament\Resources\VdjBlogCategories\Pages\EditVdjBlogCategory;
use App\Filament\Resources\VdjBlogCategories\Pages\ListVdjBlogCategories;
use App\Models\BlogCategory;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VdjBlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Legacy Data';

    protected static ?string $navigationLabel = 'Blog Categories';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(200),
                TextInput::make('slug')->required()->maxLength(200),
                TextInput::make('name_tr')->maxLength(200),
                TextInput::make('name_de')->maxLength(200),
                TextInput::make('slug_tr')->maxLength(200),
                TextInput::make('slug_de')->maxLength(200),
            ]),
            Textarea::make('description')->rows(4)->columnSpanFull(),
            Textarea::make('description_tr')->rows(4)->columnSpanFull(),
            Textarea::make('description_de')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('slug_tr')->label('TR slug'),
                TextColumn::make('slug_de')->label('DE slug'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVdjBlogCategories::route('/'),
            'edit' => EditVdjBlogCategory::route('/{record}/edit'),
        ];
    }
}
