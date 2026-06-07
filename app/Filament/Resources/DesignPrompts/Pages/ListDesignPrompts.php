<?php

namespace App\Filament\Resources\DesignPrompts\Pages;

use App\Filament\Resources\DesignPrompts\DesignPromptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDesignPrompts extends ListRecords
{
    protected static string $resource = DesignPromptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
