<?php

namespace App\Filament\Resources\Services\Tables;

use App\Filament\Resources\Services\Schemas\ServiceForm;
use App\Filament\Support\Translatable;
use App\Support\FrontendData;
use App\Support\IconLibrary;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                Translatable::column('title', 'Judul')
                    ->weight('medium'),
                Translatable::column('short_description', 'Deskripsi singkat')
                    ->limit(60)
                    ->color('gray'),
                ...Translatable::statusColumns(ServiceForm::LABELS),
                TextColumn::make('icon')
                    ->label('Ikon')
                    ->formatStateUsing(fn (?string $state): HtmlString => new HtmlString($state ? IconLibrary::optionHtml($state, $state) : '')),
                TextColumn::make('anchor')
                    ->label('Anchor')
                    ->prefix('#')
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_featured')
                    ->label('Utama'),
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
