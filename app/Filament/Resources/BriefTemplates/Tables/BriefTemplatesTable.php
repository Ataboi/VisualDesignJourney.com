<?php

namespace App\Filament\Resources\BriefTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class BriefTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('industry')->searchable(),
                \Filament\Tables\Columns\IconColumn::make('is_featured')->boolean(),
                \Filament\Tables\Columns\ToggleColumn::make('is_active'),
                \Filament\Tables\Columns\TextColumn::make('use_count')->sortable(),
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
