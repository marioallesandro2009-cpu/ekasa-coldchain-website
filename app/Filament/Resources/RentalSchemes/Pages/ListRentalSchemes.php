<?php

namespace App\Filament\Resources\RentalSchemes\Pages;

use App\Filament\Resources\RentalSchemes\RentalSchemeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRentalSchemes extends ListRecords
{
    protected static string $resource = RentalSchemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
