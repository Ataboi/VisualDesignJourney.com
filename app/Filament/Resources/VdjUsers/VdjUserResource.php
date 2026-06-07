<?php

namespace App\Filament\Resources\VdjUsers;

use App\Filament\Resources\VdjUsers\Pages\EditVdjUser;
use App\Filament\Resources\VdjUsers\Pages\ListVdjUsers;
use App\Models\User;
use BackedEnum;
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

class VdjUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Legacy Data';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('username')->required()->maxLength(50),
                TextInput::make('email')->email()->required()->maxLength(255),
                TextInput::make('password_hash')
                    ->label('New password')
                    ->password()
                    ->dehydrated(fn ($state): bool => filled($state)),
                TextInput::make('avatar')->maxLength(255),
                TextInput::make('website')->maxLength(255),
                TextInput::make('location')->maxLength(100),
                Toggle::make('is_admin'),
                Toggle::make('is_verified'),
                Toggle::make('is_onboarded'),
            ]),
            Textarea::make('bio')->rows(5)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('username')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                IconColumn::make('is_admin')->boolean(),
                IconColumn::make('is_verified')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVdjUsers::route('/'),
            'edit' => EditVdjUser::route('/{record}/edit'),
        ];
    }
}
