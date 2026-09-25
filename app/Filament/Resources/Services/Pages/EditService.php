<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Support\ClearsSampleFlag;
use App\Models\Service;
use App\Support\Locales;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Service $record
 */
class EditService extends EditRecord
{
    use ClearsSampleFlag;

    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        // Open the detail page in each language; a draft opens as a preview (admins only).
        $previews = array_map(fn (string $locale): Action => Action::make("preview_{$locale}")
            ->label('Halaman '.strtoupper($locale))
            ->url(fn (): string => $this->record->detailPath($locale))
            ->openUrlInNewTab(), Locales::all());

        return [
            ActionGroup::make($previews)
                ->label(fn (): string => $this->record->isPublished() ? 'Lihat halaman' : 'Pratinjau')
                ->icon(Heroicon::OutlinedEye)
                ->button()
                ->color('gray'),
            DeleteAction::make(),
        ];
    }
}
