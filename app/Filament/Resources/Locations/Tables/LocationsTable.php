<?php

namespace App\Filament\Resources\Locations\Tables;

use App\Filament\Resources\Locations\Schemas\LocationForm;
use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\Location;
use App\Support\FrontendData;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')->label('Foto')->collection('photo')->imageHeight(40),
                TextColumn::make('name')
                    ->label('Nama')
                    ->description(fn (Location $record): string => trim($record->city.($record->area ? ', '.$record->area : '')))
                    ->searchable(['name', 'city', 'area'])
                    ->weight('medium'),
                TextColumn::make('kind')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => Location::KINDS[$state] ?? $state)
                    ->badge()
                    ->color('gray'),
                TextColumn::make('lat')
                    ->label('Koordinat')
                    ->formatStateUsing(fn (Location $record): ?string => $record->lat !== null ? "{$record->lat}, {$record->lng}" : null)
                    ->placeholder('Belum diisi')
                    ->color('gray'),
                ...Translatable::statusColumns(LocationForm::LABELS),
                Sample::column(),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([
                SelectFilter::make('kind')->label('Jenis')->options(Location::KINDS),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
