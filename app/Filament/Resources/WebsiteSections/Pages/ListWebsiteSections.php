<?php

namespace App\Filament\Resources\WebsiteSections\Pages;

use App\Filament\Resources\WebsiteSections\WebsiteSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteSections extends ListRecords
{
    protected static string $resource = WebsiteSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
