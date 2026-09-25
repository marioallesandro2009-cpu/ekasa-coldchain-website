<?php

namespace App\Filament\Resources\RentalSchemes\Tables;

use App\Filament\Resources\RentalSchemes\Schemas\RentalSchemeForm;
use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\RentalScheme;
use App\Support\FrontendData;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class RentalSchemesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                Translatable::column('name', 'Nama')->weight('medium'),
                TextColumn::make('durations')
                    ->label('Durasi')
                    ->state(fn (RentalScheme $record): array => array_map(fn (string $key): string => RentalScheme::DURATIONS[$key][1] ?? $key, (array) $record->durations))
                    ->badge()
                    ->color('gray'),
                ...Translatable::statusColumns(RentalSchemeForm::LABELS),
                Sample::column(),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
