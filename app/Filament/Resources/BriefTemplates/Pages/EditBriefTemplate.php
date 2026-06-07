<?php

namespace App\Filament\Resources\BriefTemplates\Pages;

use App\Filament\Resources\BriefTemplates\BriefTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBriefTemplate extends EditRecord
{
    protected static string $resource = BriefTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
