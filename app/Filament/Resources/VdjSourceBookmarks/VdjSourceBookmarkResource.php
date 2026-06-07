<?php

namespace App\Filament\Resources\VdjSourceBookmarks;

use App\Filament\Resources\VdjSourceBookmarks\Pages\EditVdjSourceBookmark;
use App\Filament\Resources\VdjSourceBookmarks\Pages\ListVdjSourceBookmarks;
use App\Models\SourceBookmark;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VdjSourceBookmarkResource extends Resource
{
    protected static ?string $model = SourceBookmark::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Legacy Data';

    protected static ?string $navigationLabel = 'Source Queue';

    protected static ?int $navigationSort = 43;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('source_url')->required()->maxLength(1000),
                TextInput::make('source_platform')->maxLength(120),
                TextInput::make('title')->maxLength(255),
                TextInput::make('image_url')->maxLength(1000),
                TextInput::make('suggested_tags')->maxLength(255),
                Select::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ]),
                TextInput::make('board_id')->numeric(),
            ]),
            Textarea::make('description')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('title')->searchable()->limit(42),
                TextColumn::make('source_url')->searchable()->limit(54),
                TextColumn::make('source_platform')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVdjSourceBookmarks::route('/'),
            'edit' => EditVdjSourceBookmark::route('/{record}/edit'),
        ];
    }
}
