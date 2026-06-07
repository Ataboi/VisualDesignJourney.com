<?php

namespace App\Filament\Resources\VdjBlogPosts\Pages;

use App\Filament\Resources\VdjBlogPosts\VdjBlogPostResource;
use Filament\Resources\Pages\ListRecords;

class ListVdjBlogPosts extends ListRecords
{
    protected static string $resource = VdjBlogPostResource::class;
}
