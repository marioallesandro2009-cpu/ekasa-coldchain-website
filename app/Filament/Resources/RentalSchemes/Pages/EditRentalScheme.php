<?php

namespace App\Filament\Resources\RentalSchemes\Pages;

use App\Filament\Resources\RentalSchemes\RentalSchemeResource;
use App\Filament\Support\ClearsSampleFlag;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRentalScheme extends EditRecord
{
    use ClearsSampleFlag;

    protected static string $resource = RentalSchemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
