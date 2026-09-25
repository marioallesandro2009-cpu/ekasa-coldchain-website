<?php

namespace App\Filament\Resources\Faqs\Tables;

use App\Filament\Resources\Faqs\Schemas\FaqForm;
use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Support\FrontendData;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                Translatable::column('question', 'Pertanyaan')->weight('medium')->wrap(),
                ...Translatable::statusColumns(FaqForm::LABELS),
                Sample::column(),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
