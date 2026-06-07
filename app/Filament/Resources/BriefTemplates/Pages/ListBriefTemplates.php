<?php

namespace App\Filament\Resources\BriefTemplates\Pages;

use App\Filament\Resources\BriefTemplates\BriefTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBriefTemplates extends ListRecords
{
    protected static string $resource = BriefTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
