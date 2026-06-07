<?php

namespace App\Filament\Resources\WebsiteSections\Pages;

use App\Filament\Resources\WebsiteSections\WebsiteSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteSection extends EditRecord
{
    protected static string $resource = WebsiteSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
