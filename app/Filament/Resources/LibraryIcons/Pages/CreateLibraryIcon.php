<?php

namespace App\Filament\Resources\LibraryIcons\Pages;

use App\Filament\Resources\LibraryIcons\LibraryIconResource;
use App\Models\LibraryIcon;
use App\Support\IconLibrary;
use Filament\Resources\Pages\CreateRecord;

class CreateLibraryIcon extends CreateRecord
{
    protected static string $resource = LibraryIconResource::class;

    protected static ?string $title = 'Unggah ikon SVG';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Already validated by the form; stored in its cleaned form.
        $data['svg'] = IconLibrary::sanitize((string) $data['svg']);
        $data['source'] = LibraryIcon::SOURCE_UPLOAD;
        $data['sort_order'] = (int) LibraryIcon::query()->max('sort_order') + 1;

        return $data;
    }
}
