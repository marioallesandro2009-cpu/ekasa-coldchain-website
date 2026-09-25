<?php

namespace App\Filament\Resources\TransportRoutes\Tables;

use App\Filament\Support\Sample;
use App\Models\TransportRoute;
use App\Support\FrontendData;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TransportRoutesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                TextColumn::make('origin')
                    ->label('Rute')
                    ->formatStateUsing(fn (TransportRoute $record): string => $record->label())
                    ->searchable(['origin', 'destination'])
                    ->weight('medium'),
                TextColumn::make('eta_hours_min')
                    ->label('Estimasi')
                    ->formatStateUsing(fn (TransportRoute $record): ?string => $record->eta_hours_min ? "{$record->eta_hours_min}-{$record->eta_hours_max} jam" : null)
                    ->placeholder('—'),
                Sample::column(),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
