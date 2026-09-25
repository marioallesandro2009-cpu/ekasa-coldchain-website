<?php

namespace App\Filament\Resources\FleetVehicles\Tables;

use App\Filament\Resources\FleetVehicles\Schemas\FleetVehicleForm;
use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\FleetVehicle;
use App\Support\FrontendData;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class FleetVehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                SpatieMediaLibraryImageColumn::make('side')
                    ->label('Tampak samping')
                    ->collection('side')
                    ->imageHeight(40),
                TextColumn::make('model')
                    ->label('Truk')
                    ->formatStateUsing(fn (FleetVehicle $record): string => $record->name())
                    ->description(fn (FleetVehicle $record): string => FleetVehicle::TYPES[$record->type] ?? $record->type)
                    ->searchable(['model', 'brand'])
                    ->weight('medium'),
                TextColumn::make('temp_min')
                    ->label('Suhu')
                    ->formatStateUsing(fn (FleetVehicle $record): ?string => $record->temp_min !== null ? "{$record->temp_min} s/d {$record->temp_max} °C" : null)
                    ->placeholder('—'),
                TextColumn::make('capacity_kg')
                    ->label('Kapasitas')
                    ->numeric(locale: 'id')
                    ->suffix(' kg')
                    ->placeholder('—'),
                TextColumn::make('side_width')
                    ->label('Lebar gambar')
                    ->state(fn (FleetVehicle $record): ?string => ($width = $record->sideWidth()) ? "{$width} px" : null)
                    ->color(fn (FleetVehicle $record): string => ($record->sideWidth() ?? PHP_INT_MAX) < FleetVehicle::MIN_SIDE_WIDTH ? 'warning' : 'gray')
                    ->placeholder('—'),
                ...Translatable::statusColumns(FleetVehicleForm::LABELS),
                Sample::column(),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
