<?php

namespace App\Filament\Resources\TrustLogos\Pages;

use App\Filament\Resources\TrustLogos\TrustLogoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrustLogo extends EditRecord
{
    protected static string $resource = TrustLogoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
