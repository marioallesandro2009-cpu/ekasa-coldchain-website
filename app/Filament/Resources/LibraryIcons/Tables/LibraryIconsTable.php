<?php

namespace App\Filament\Resources\LibraryIcons\Tables;

use App\Filament\Resources\LibraryIcons\LibraryIconResource;
use App\Models\LibraryIcon;
use App\Support\IconLibrary;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class LibraryIconsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->paginated([24, 48, 96])
            ->defaultPaginationPageOption(48)
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->columns([
                Stack::make([
                    TextColumn::make('preview')
                        ->state(fn (LibraryIcon $record): HtmlString => LibraryIconResource::preview($record->svg)),
                    Split::make([
                        Stack::make([
                            TextColumn::make('label_id')
                                ->state(fn (LibraryIcon $record): string => $record->label())
                                ->weight('medium')
                                ->searchable(['label_id', 'label_en', 'key']),
                            TextColumn::make('key')
                                ->formatStateUsing(fn (LibraryIcon $record): string => $record->key.' · '.(IconLibrary::categories()[$record->category] ?? $record->category))
                                ->color('gray')
                                ->size('sm'),
                            TextColumn::make('usage')
                                ->state(fn (LibraryIcon $record): ?string => ($count = count((LibraryIcon::usageMap()[$record->key] ?? []))) > 0 ? "Dipakai {$count}x" : null)
                                ->tooltip(fn (LibraryIcon $record): ?string => implode(', ', (LibraryIcon::usageMap()[$record->key] ?? [])) ?: null)
                                ->badge()
                                ->color('success'),
                        ])->space(1),
                        ToggleColumn::make('is_active')
                            ->tooltip('Aktif: bisa dipilih di Layanan dan Statistik')
                            ->disabled(fn (LibraryIcon $record): bool => $record->is_active && (LibraryIcon::usageMap()[$record->key] ?? []) !== [])
                            ->grow(false),
                    ]),
                ])->space(3),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(IconLibrary::categories()),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif (termasuk menunggu persetujuan)'),
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options([LibraryIcon::SOURCE_EKASA => 'Ikon Ekasa', LibraryIcon::SOURCE_UPLOAD => 'Unggahan admin']),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
