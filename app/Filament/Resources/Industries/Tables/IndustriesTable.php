<?php

namespace App\Filament\Resources\Industries\Tables;

use App\Filament\Resources\Industries\Schemas\IndustryForm;
use App\Filament\Support\Sample;
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

class IndustriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                TextColumn::make('icon')
                    ->label('Ikon')
                    ->formatStateUsing(fn (?string $state): HtmlString => new HtmlString($state ? IconLibrary::svg($state, 'width:1.5rem;height:1.5rem;color:var(--primary-600)') : '')),
                Translatable::column('name', 'Nama')->weight('medium'),
                ...Translatable::statusColumns(IndustryForm::LABELS),
                Sample::column(),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
