<?php

namespace App\Filament\Resources\TrustLogos;

use App\Filament\Resources\TrustLogos\Pages\CreateTrustLogo;
use App\Filament\Resources\TrustLogos\Pages\EditTrustLogo;
use App\Filament\Resources\TrustLogos\Pages\ListTrustLogos;
use App\Filament\Resources\TrustLogos\Schemas\TrustLogoForm;
use App\Filament\Resources\TrustLogos\Tables\TrustLogosTable;
use App\Models\TrustLogo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TrustLogoResource extends Resource
{
    protected static ?string $model = TrustLogo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'logo klien';

    protected static ?string $pluralModelLabel = 'Logo Klien';

    protected static ?string $navigationLabel = 'Logo Klien';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return TrustLogoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrustLogosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrustLogos::route('/'),
            'create' => CreateTrustLogo::route('/create'),
            'edit' => EditTrustLogo::route('/{record}/edit'),
        ];
    }
}
