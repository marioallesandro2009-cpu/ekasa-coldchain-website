<?php

namespace App\Filament\Resources\HeroStats\Tables;

use App\Filament\Resources\HeroStats\Schemas\HeroStatForm;
use App\Filament\Support\Translatable;
use App\Models\HeroStat;
use App\Support\FrontendData;
use App\Support\ServiceIcons;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class HeroStatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                TextColumn::make('display_value')
                    ->label('Nilai')
                    ->state(function (HeroStat $record): string {
                        $stat = $record->toFrontend();

                        return $stat['value'] !== null
                            ? $stat['prefix'].number_format($stat['value'], $stat['decimals']).$stat['suffix']
                            : (string) $stat['textValue'];
                    })
                    ->weight('bold'),
                Translatable::column('label', 'Label'),
                TextColumn::make('icon')
                    ->label('Ikon')
                    ->formatStateUsing(fn (?string $state): string => ServiceIcons::OPTIONS[$state] ?? (string) $state)
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('use_count_up')
                    ->label('Count Up')
                    ->boolean(),
                ...Translatable::statusColumns(HeroStatForm::LABELS),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
