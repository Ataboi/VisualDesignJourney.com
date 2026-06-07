<?php

namespace App\Filament\Resources\VdjBlogPosts;

use App\Filament\Resources\VdjBlogPosts\Pages\EditVdjBlogPost;
use App\Filament\Resources\VdjBlogPosts\Pages\ListVdjBlogPosts;
use App\Models\BlogPost;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VdjBlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Legacy Data';

    protected static ?string $navigationLabel = 'Blog Posts';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('title')->required()->maxLength(500),
                TextInput::make('slug')->required()->maxLength(500),
                TextInput::make('title_tr')->maxLength(500),
                TextInput::make('title_de')->maxLength(500),
                TextInput::make('slug_tr')->maxLength(500),
                TextInput::make('slug_de')->maxLength(500),
                TextInput::make('featured_image')->maxLength(500),
                TextInput::make('author')->maxLength(100),
                DateTimePicker::make('published_at'),
            ]),
            Textarea::make('excerpt')->rows(3)->columnSpanFull(),
            Textarea::make('excerpt_tr')->rows(3)->columnSpanFull(),
            Textarea::make('excerpt_de')->rows(3)->columnSpanFull(),
            Textarea::make('content')->rows(16)->columnSpanFull(),
            Textarea::make('content_tr')->rows(12)->columnSpanFull(),
            Textarea::make('content_de')->rows(12)->columnSpanFull(),
            Grid::make(2)->schema([
                TextInput::make('seo_title')->maxLength(200),
                TextInput::make('seo_description')->maxLength(300),
                TextInput::make('seo_title_tr')->maxLength(200),
                TextInput::make('seo_description_tr')->maxLength(300),
                TextInput::make('seo_title_de')->maxLength(200),
                TextInput::make('seo_description_de')->maxLength(300),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('title')->searchable()->sortable()->limit(48),
                TextColumn::make('slug')->searchable()->limit(42),
                TextColumn::make('slug_tr')->label('TR slug')->limit(32),
                TextColumn::make('slug_de')->label('DE slug')->limit(32),
                TextColumn::make('published_at')->dateTime()->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVdjBlogPosts::route('/'),
            'edit' => EditVdjBlogPost::route('/{record}/edit'),
        ];
    }
}
