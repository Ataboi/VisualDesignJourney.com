<?php

namespace App\Filament\Resources\DesignPrompts\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DesignPromptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    Select::make('type')
                        ->options([
                            'visual' => 'Visual',
                            'ui' => 'UI/UX',
                            'branding' => 'Branding',
                            'motion' => 'Motion',
                            'illustration' => 'Illustration',
                            'typography' => 'Typography',
                        ])
                        ->required(),
                    Select::make('difficulty')
                        ->options([
                            'easy' => 'Easy',
                            'medium' => 'Medium',
                            'hard' => 'Hard',
                        ])
                        ->required(),
                    TextInput::make('tool')->placeholder('Figma, Photoshop, Any...'),
                    Select::make('category_id')
                        ->label('Category')
                        ->options(Category::where('is_active', true)->pluck('name', 'id'))
                        ->searchable(),
                    Select::make('tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->preload(),
                    Toggle::make('is_featured')->default(false),
                    Toggle::make('is_active')->default(true),
                ]),
                Textarea::make('prompt')->required()->rows(5)->columnSpanFull(),
                Textarea::make('notes')->rows(3)->columnSpanFull(),
            ]);
    }
}
