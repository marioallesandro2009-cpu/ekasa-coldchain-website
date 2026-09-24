<?php

namespace App\Filament\Resources\PageSections\Pages;

use App\Filament\Resources\PageSections\PageSectionResource;
use Filament\Resources\Pages\ListRecords;

class ListPageSections extends ListRecords
{
    protected static string $resource = PageSectionResource::class;

    public function getSubheading(): ?string
    {
        return 'Urutan di sini = urutan section di halaman dan urutan menu navbar. Seret untuk mengubah urutan.';
    }
}
