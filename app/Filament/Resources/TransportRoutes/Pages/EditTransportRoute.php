<?php

namespace App\Filament\Resources\TransportRoutes\Pages;

use App\Filament\Resources\TransportRoutes\TransportRouteResource;
use App\Filament\Support\ClearsSampleFlag;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransportRoute extends EditRecord
{
    use ClearsSampleFlag;

    protected static string $resource = TransportRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
