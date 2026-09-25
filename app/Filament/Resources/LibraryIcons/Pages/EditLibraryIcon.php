<?php

namespace App\Filament\Resources\LibraryIcons\Pages;

use App\Filament\Resources\LibraryIcons\LibraryIconResource;
use App\Models\LibraryIcon;
use App\Support\IconLibrary;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property LibraryIcon $record
 */
class EditLibraryIcon extends EditRecord
{
    protected static string $resource = LibraryIconResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // The drawing of an Ekasa icon comes from its file and the key is fixed
        // for every icon (content refers to it).
        unset($data['key']);

        if ($this->record->isBuiltIn()) {
            unset($data['svg']);
        } elseif (isset($data['svg'])) {
            $data['svg'] = IconLibrary::sanitize((string) $data['svg']);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (LibraryIcon $record): bool => ! $record->isBuiltIn() && $record->usages() === []),
        ];
    }
}
