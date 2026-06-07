<?php

namespace App\Filament\Resources\FontPairings\Schemas;

use App\Models\Category;
use App\Models\Tag;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FontPairingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    TextInput::make('heading_font')->required()->placeholder('Playfair Display'),
                    TextInput::make('body_font')->required()->placeholder('Inter'),
                    TextInput::make('heading_weight')->default('700'),
                    TextInput::make('body_weight')->default('400'),
                    TextInput::make('heading_size')->default('3rem'),
                    TextInput::make('body_size')->default('1rem'),
                    Select::make('style')
                        ->options([
                            'editorial' => 'Editorial',
                            'minimal' => 'Minimal',
                            'bold' => 'Bold',
                            'elegant' => 'Elegant',
                            'modern' => 'Modern',
                            'playful' => 'Playful',
                        ]),
                    Select::make('category_id')
                        ->label('Category')
                        ->options(Category::where('is_active', true)->pluck('name', 'id'))
                        ->searchable(),
                    Select::make('tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->preload(),
                    TextInput::make('preview_text')->placeholder('The quick brown fox...'),
                    Toggle::make('is_featured')->default(false),
                    Toggle::make('is_active')->default(true),
                ]),
                Textarea::make('description')->columnSpanFull(),
            ]);
    }
}
