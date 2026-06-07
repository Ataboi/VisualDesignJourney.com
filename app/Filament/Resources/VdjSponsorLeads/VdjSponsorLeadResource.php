<?php

namespace App\Filament\Resources\VdjSponsorLeads;

use App\Filament\Resources\VdjSponsorLeads\Pages\EditVdjSponsorLead;
use App\Filament\Resources\VdjSponsorLeads\Pages\ListVdjSponsorLeads;
use App\Models\SponsorLead;
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

class VdjSponsorLeadResource extends Resource
{
    protected static ?string $model = SponsorLead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Legacy Data';

    protected static ?string $navigationLabel = 'Sponsor Leads';

    protected static ?int $navigationSort = 41;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('email')->email()->required()->maxLength(190),
                TextInput::make('company')->maxLength(160),
                TextInput::make('budget')->maxLength(40),
                Select::make('status')->options([
                    'new' => 'New',
                    'contacted' => 'Contacted',
                    'won' => 'Won',
                    'lost' => 'Lost',
                ]),
            ]),
            Textarea::make('message')->rows(6)->columnSpanFull(),
            Textarea::make('notes')->rows(5)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('company')->searchable(),
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
            'index' => ListVdjSponsorLeads::route('/'),
            'edit' => EditVdjSponsorLead::route('/{record}/edit'),
        ];
    }
}
