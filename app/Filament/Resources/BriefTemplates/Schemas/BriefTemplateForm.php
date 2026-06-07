<?php

namespace App\Filament\Resources\BriefTemplates\Schemas;

use App\Models\Category;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BriefTemplateForm
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
                    TextInput::make('industry')->placeholder('Tech, Fashion, Food...'),
                    Select::make('category_id')
                        ->label('Category')
                        ->options(Category::where('is_active', true)->pluck('name', 'id'))
                        ->searchable(),
                    Toggle::make('is_featured')->default(false),
                    Toggle::make('is_active')->default(true),
                ]),
                Textarea::make('description')->columnSpanFull(),
                Repeater::make('fields')
                    ->label('Brief Fields')
                    ->schema([
                        TextInput::make('label')->required(),
                        TextInput::make('key')->required(),
                        Select::make('type')
                            ->options(['text' => 'Text', 'textarea' => 'Textarea', 'select' => 'Select'])
                            ->default('text'),
                        TextInput::make('placeholder'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
