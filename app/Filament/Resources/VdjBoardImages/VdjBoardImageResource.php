<?php

namespace App\Filament\Resources\VdjBoardImages;

use App\Filament\Resources\VdjBoardImages\Pages\EditVdjBoardImage;
use App\Filament\Resources\VdjBoardImages\Pages\ListVdjBoardImages;
use App\Models\BoardImage;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VdjBoardImageResource extends Resource
{
    protected static ?string $model = BoardImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Legacy Data';

    protected static ?string $navigationLabel = 'Board Images';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Select::make('board_id')->relationship('board', 'title')->searchable()->required(),
                TextInput::make('image_url')->required()->maxLength(1000),
                TextInput::make('source_url')->maxLength(500),
                TextInput::make('source_platform')->maxLength(80),
                TextInput::make('credit')->maxLength(160),
                TextInput::make('caption')->maxLength(255),
                TextInput::make('sort_order')->numeric(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('board.title')->label('Board')->searchable(),
                TextColumn::make('image_url')->limit(48)->searchable(),
                TextColumn::make('source_platform')->searchable(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVdjBoardImages::route('/'),
            'edit' => EditVdjBoardImage::route('/{record}/edit'),
        ];
    }
}
