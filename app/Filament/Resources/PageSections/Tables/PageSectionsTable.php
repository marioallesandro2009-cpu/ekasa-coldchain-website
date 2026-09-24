<?php

namespace App\Filament\Resources\PageSections\Tables;

use App\Filament\Resources\PageSections\Schemas\PageSectionForm;
use App\Filament\Support\Translatable;
use App\Models\PageSection;
use App\Support\FrontendData;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PageSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                Translatable::column('title', 'Judul section')
                    ->searchable(false)
                    ->description(fn (PageSection $record): string => '#'.$record->key)
                    ->weight('medium'),
                Translatable::column('nav_label', 'Label menu')
                    ->searchable(false)
                    ->placeholder('—'),
                ...Translatable::statusColumns(PageSectionForm::LABELS),
                TextColumn::make('background')
                    ->label('Latar')
                    ->formatStateUsing(fn (string $state): string => PageSection::BACKGROUNDS[$state] ?? $state)
                    ->badge()
                    ->color('gray'),
                ToggleColumn::make('show_in_nav')
                    ->label('Di menu'),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
