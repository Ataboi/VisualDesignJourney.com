<?php

namespace App\Filament\Resources\WebsiteSections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class WebsiteSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('preview_image'),
                \Filament\Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                \Filament\Tables\Columns\BadgeColumn::make('section_type'),
                \Filament\Tables\Columns\BadgeColumn::make('style'),
                \Filament\Tables\Columns\IconColumn::make('is_featured')->boolean(),
                \Filament\Tables\Columns\ToggleColumn::make('is_active'),
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
