<?php

namespace App\Filament\Resources\LibraryIcons;

use App\Filament\Resources\LibraryIcons\Pages\CreateLibraryIcon;
use App\Filament\Resources\LibraryIcons\Pages\EditLibraryIcon;
use App\Filament\Resources\LibraryIcons\Pages\ListLibraryIcons;
use App\Filament\Resources\LibraryIcons\Schemas\LibraryIconForm;
use App\Filament\Resources\LibraryIcons\Tables\LibraryIconsTable;
use App\Models\LibraryIcon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class LibraryIconResource extends Resource
{
    protected static ?string $model = LibraryIcon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'ikon';

    protected static ?string $pluralModelLabel = 'Pustaka Ikon';

    protected static ?string $navigationLabel = 'Pustaka Ikon';

    protected static ?string $recordTitleAttribute = 'label_id';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return LibraryIconForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LibraryIconsTable::configure($table);
    }

    /**
     * An icon on a light and a dark tile, at two sizes (inline styles: the admin
     * theme has no classes for the site colours).
     */
    public static function preview(string $svg, int $large = 32, int $small = 20): HtmlString
    {
        $sized = fn (int $size): string => preg_replace('/^<svg\b/', '<svg width="'.$size.'" height="'.$size.'" style="display:block"', $svg, 1) ?? '';
        $tile = fn (string $style): string => '<span style="display:inline-flex;align-items:center;gap:12px;padding:10px 12px;border-radius:8px;'.$style.'">'
            .$sized($large).$sized($small).'</span>';

        return new HtmlString('<span style="display:inline-flex;gap:6px">'
            .$tile('background:#fff;color:#006C5E;box-shadow:inset 0 0 0 1px #D0D5DE')
            .$tile('background:#142421;color:#fff')
            .'</span>');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLibraryIcons::route('/'),
            'create' => CreateLibraryIcon::route('/create'),
            'edit' => EditLibraryIcon::route('/{record}/edit'),
        ];
    }
}
