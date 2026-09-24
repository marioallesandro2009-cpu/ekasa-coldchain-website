<?php

namespace App\Filament\Resources\HeroStats;

use App\Filament\Resources\HeroStats\Pages\CreateHeroStat;
use App\Filament\Resources\HeroStats\Pages\EditHeroStat;
use App\Filament\Resources\HeroStats\Pages\ListHeroStats;
use App\Filament\Resources\HeroStats\Schemas\HeroStatForm;
use App\Filament\Resources\HeroStats\Tables\HeroStatsTable;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Models\HeroStat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class HeroStatResource extends Resource
{
    use HasTranslatableRecordTitle;

    protected static ?string $model = HeroStat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'statistik hero';

    protected static ?string $pluralModelLabel = 'Statistik Hero';

    protected static ?string $navigationLabel = 'Statistik Hero';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return HeroStatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HeroStatsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHeroStats::route('/'),
            'create' => CreateHeroStat::route('/create'),
            'edit' => EditHeroStat::route('/{record}/edit'),
        ];
    }
}
