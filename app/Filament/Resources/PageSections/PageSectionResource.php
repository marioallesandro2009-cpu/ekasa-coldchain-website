<?php

namespace App\Filament\Resources\PageSections;

use App\Filament\Resources\PageSections\Pages\EditPageSection;
use App\Filament\Resources\PageSections\Pages\ListPageSections;
use App\Filament\Resources\PageSections\Schemas\PageSectionForm;
use App\Filament\Resources\PageSections\Tables\PageSectionsTable;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Models\PageSection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Home page sections and the navbar menu. Sections are fixed in code (their
 * layout is built per stage), so they can be edited but not created or deleted.
 */
class PageSectionResource extends Resource
{
    use HasTranslatableRecordTitle;

    protected static ?string $model = PageSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'section';

    protected static ?string $pluralModelLabel = 'Section & Menu';

    protected static ?string $navigationLabel = 'Section & Menu';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return PageSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PageSectionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageSections::route('/'),
            'edit' => EditPageSection::route('/{record}/edit'),
        ];
    }
}
