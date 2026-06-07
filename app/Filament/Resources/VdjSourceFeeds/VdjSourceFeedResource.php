<?php

namespace App\Filament\Resources\VdjSourceFeeds;

use App\Filament\Resources\VdjSourceFeeds\Pages\EditVdjSourceFeed;
use App\Filament\Resources\VdjSourceFeeds\Pages\ListVdjSourceFeeds;
use App\Models\SourceFeed;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VdjSourceFeedResource extends Resource
{
    protected static ?string $model = SourceFeed::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Legacy Data';

    protected static ?string $navigationLabel = 'Source Feeds';

    protected static ?int $navigationSort = 42;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(160),
                TextInput::make('feed_url')->required()->maxLength(1000),
                TextInput::make('tags')->maxLength(255),
                Toggle::make('is_active'),
                DateTimePicker::make('last_run_at'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('feed_url')->limit(54)->searchable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('last_run_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVdjSourceFeeds::route('/'),
            'edit' => EditVdjSourceFeed::route('/{record}/edit'),
        ];
    }
}
