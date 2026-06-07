<?php

namespace App\Filament\Resources\WebsiteSections\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class WebsiteSectionForm
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
                    Select::make('section_type')
                        ->options([
                            'hero' => 'Hero',
                            'navbar' => 'Navigation',
                            'features' => 'Features',
                            'pricing' => 'Pricing',
                            'testimonials' => 'Testimonials',
                            'footer' => 'Footer',
                            'cta' => 'Call to Action',
                            'faq' => 'FAQ',
                            'gallery' => 'Gallery',
                        ])
                        ->required(),
                    Select::make('style')
                        ->options([
                            'minimal' => 'Minimal',
                            'bold' => 'Bold',
                            'editorial' => 'Editorial',
                            'glassmorphism' => 'Glassmorphism',
                            'dark' => 'Dark',
                            'light' => 'Light',
                        ]),
                    Select::make('category_id')
                        ->label('Category')
                        ->options(Category::where('is_active', true)->pluck('name', 'id'))
                        ->searchable(),
                    TextInput::make('source_url')->url()->placeholder('https://...'),
                    Select::make('tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->preload(),
                    Toggle::make('is_featured')->default(false),
                    Toggle::make('is_active')->default(true),
                ]),
                Textarea::make('description')->columnSpanFull(),
                FileUpload::make('preview_image')
                    ->image()
                    ->directory('sections')
                    ->columnSpanFull(),
            ]);
    }
}
