<?php

namespace App\Filament\Resources\DesignPrompts\Pages;

use App\Filament\Resources\DesignPrompts\DesignPromptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDesignPrompt extends EditRecord
{
    protected static string $resource = DesignPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
