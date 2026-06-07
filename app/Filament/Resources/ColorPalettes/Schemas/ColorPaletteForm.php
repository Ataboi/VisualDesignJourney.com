<?php

namespace App\Filament\Resources\ColorPalettes\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ColorPaletteForm
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
                    Select::make('mood')
                        ->options([
                            'dark' => 'Dark',
                            'light' => 'Light',
                            'vibrant' => 'Vibrant',
                            'pastel' => 'Pastel',
                            'earthy' => 'Earthy',
                            'monochrome' => 'Monochrome',
                            'neon' => 'Neon',
                        ]),
                    Select::make('category_id')
                        ->label('Category')
                        ->options(Category::where('is_active', true)->pluck('name', 'id'))
                        ->searchable(),
                    Toggle::make('is_featured')->default(false),
                    Toggle::make('is_active')->default(true),
                ]),
                Textarea::make('description')->columnSpanFull(),
                Repeater::make('colors')
                    ->label('Color Swatches (hex codes)')
                    ->schema([
                        TextInput::make('color')->placeholder('#1a1a2e')->required(),
                    ])
                    ->columnSpanFull()
                    ->minItems(2)
                    ->maxItems(8)
                    ->simple(TextInput::make('color')->placeholder('#1a1a2e')),
            ]);
    }
}
