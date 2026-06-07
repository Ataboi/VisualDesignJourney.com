<?php

namespace App\Filament\Pages;

use App\Support\StructuredDataValidator;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class StructuredDataHealth extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'VDJ Health';

    protected static ?string $navigationLabel = 'Structured Data';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.structured-data-health';

    /** @var array<int, array{name: string, status: string, message: string}> */
    public array $checks = [];

    public function mount(StructuredDataValidator $validator): void
    {
        $this->checks = $validator->checks();
    }
}
