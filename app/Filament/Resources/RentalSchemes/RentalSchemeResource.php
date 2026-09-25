<?php

namespace App\Filament\Resources\RentalSchemes;

use App\Filament\Resources\RentalSchemes\Pages\CreateRentalScheme;
use App\Filament\Resources\RentalSchemes\Pages\EditRentalScheme;
use App\Filament\Resources\RentalSchemes\Pages\ListRentalSchemes;
use App\Filament\Resources\RentalSchemes\Schemas\RentalSchemeForm;
use App\Filament\Resources\RentalSchemes\Tables\RentalSchemesTable;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Models\RentalScheme;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RentalSchemeResource extends Resource
{
    use HasTranslatableRecordTitle;

    protected static ?string $model = RentalScheme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Data Layanan';

    protected static ?string $modelLabel = 'skema sewa';

    protected static ?string $pluralModelLabel = 'Skema Sewa';

    protected static ?string $navigationLabel = 'Skema Sewa';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return RentalSchemeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RentalSchemesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRentalSchemes::route('/'),
            'create' => CreateRentalScheme::route('/create'),
            'edit' => EditRentalScheme::route('/{record}/edit'),
        ];
    }
}
