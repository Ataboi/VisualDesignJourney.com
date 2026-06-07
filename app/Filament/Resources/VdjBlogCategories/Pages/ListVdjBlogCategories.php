<?php

namespace App\Filament\Resources\VdjBlogCategories\Pages;

use App\Filament\Resources\VdjBlogCategories\VdjBlogCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListVdjBlogCategories extends ListRecords
{
    protected static string $resource = VdjBlogCategoryResource::class;
}
