<?php

namespace App\Filament\Resources\ColorPalettes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class ColorPalettesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                \Filament\Tables\Columns\BadgeColumn::make('mood'),
                \Filament\Tables\Columns\IconColumn::make('is_featured')->boolean(),
                \Filament\Tables\Columns\ToggleColumn::make('is_active'),
                \Filament\Tables\Columns\TextColumn::make('view_count')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
