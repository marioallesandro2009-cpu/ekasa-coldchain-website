<?php

namespace App\Filament\Resources\TransportRoutes;

use App\Filament\Resources\TransportRoutes\Pages\CreateTransportRoute;
use App\Filament\Resources\TransportRoutes\Pages\EditTransportRoute;
use App\Filament\Resources\TransportRoutes\Pages\ListTransportRoutes;
use App\Filament\Resources\TransportRoutes\Schemas\TransportRouteForm;
use App\Filament\Resources\TransportRoutes\Tables\TransportRoutesTable;
use App\Models\TransportRoute;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TransportRouteResource extends Resource
{
    protected static ?string $model = TransportRoute::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Data Layanan';

    protected static ?string $modelLabel = 'rute';

    protected static ?string $pluralModelLabel = 'Rute';

    protected static ?string $navigationLabel = 'Rute';

    protected static ?string $recordTitleAttribute = 'origin';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return TransportRouteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransportRoutesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransportRoutes::route('/'),
            'create' => CreateTransportRoute::route('/create'),
            'edit' => EditTransportRoute::route('/{record}/edit'),
        ];
    }
}
