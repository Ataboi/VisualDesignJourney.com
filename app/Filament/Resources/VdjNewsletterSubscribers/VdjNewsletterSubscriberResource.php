<?php

namespace App\Filament\Resources\VdjNewsletterSubscribers;

use App\Filament\Resources\VdjNewsletterSubscribers\Pages\EditVdjNewsletterSubscriber;
use App\Filament\Resources\VdjNewsletterSubscribers\Pages\ListVdjNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VdjNewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Legacy Data';

    protected static ?string $navigationLabel = 'Newsletter';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('email')->email()->required()->maxLength(255),
                TextInput::make('name')->maxLength(100),
                Select::make('status')->options([
                    'active' => 'Active',
                    'unsubscribed' => 'Unsubscribed',
                ]),
                TextInput::make('unsubscribe_token')->maxLength(64),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('name')->searchable(),
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
            'index' => ListVdjNewsletterSubscribers::route('/'),
            'edit' => EditVdjNewsletterSubscriber::route('/{record}/edit'),
        ];
    }
}
