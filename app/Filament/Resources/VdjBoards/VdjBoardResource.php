<?php

namespace App\Filament\Resources\VdjBoards;

use App\Filament\Resources\VdjBoards\Pages\EditVdjBoard;
use App\Filament\Resources\VdjBoards\Pages\ListVdjBoards;
use App\Models\Board;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VdjBoardResource extends Resource
{
    protected static ?string $model = Board::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Legacy Data';

    protected static ?string $navigationLabel = 'Boards';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Select::make('user_id')->relationship('user', 'username')->searchable()->required(),
                TextInput::make('title')->required()->maxLength(150),
                TextInput::make('slug')->maxLength(180),
                TextInput::make('cover_image')->maxLength(1000),
                TextInput::make('style_tags')->maxLength(500),
                TextInput::make('view_count')->numeric(),
                TextInput::make('save_count')->numeric(),
                Toggle::make('is_draft'),
            ]),
            Textarea::make('description')->rows(5)->columnSpanFull(),
            Grid::make(2)->schema([
                TextInput::make('title_tr')->maxLength(150),
                TextInput::make('title_de')->maxLength(150),
                TextInput::make('slug_tr')->maxLength(180),
                TextInput::make('slug_de')->maxLength(180),
            ]),
            Textarea::make('description_tr')->rows(4)->columnSpanFull(),
            Textarea::make('description_de')->rows(4)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('user.username')->label('User')->searchable(),
                IconColumn::make('is_draft')->boolean(),
                TextColumn::make('view_count')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVdjBoards::route('/'),
            'edit' => EditVdjBoard::route('/{record}/edit'),
        ];
    }
}
